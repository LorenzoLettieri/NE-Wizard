<?php

namespace Tests\Feature;

use App\Livewire\DecommissioningImport;
use App\Models\Company;
use App\Models\CompanyDecommissioningRate;
use App\Models\Decommissioning;
use App\Models\User;
use App\Services\DecommissioningMassUpdate;
use App\Support\Authorization\PermissionCatalog as P;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Import Excel come modifica massiva: CLLI come chiave, celle vuote ignorate, anteprima prima di salvare.
 */
#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class DecommissioningImportTest extends TestCase
{
    use RefreshDatabase;

    /** @var list<string> */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    public function test_analyze_previews_changes_without_writing_and_apply_saves_them(): void
    {
        $user = $this->importer(finance: true);
        $record = Decommissioning::create([
            'clli' => 'MIAMI01',
            'status' => 'Da Lavorare',
            'permesso_ente' => 'Vecchio',
            'note_permesso' => 'Da tenere',
        ]);

        $path = $this->xlsx([
            ['CLLI', 'Stato', 'Permesso Ente', 'Note Permesso', 'Data IL', 'Pagata NE', 'Colonna strana'],
            [' miami01 ', 'fine lavori', 'Nuovo', null, '15/03/2026', 'SI', 'x'],
        ]);

        $service = new DecommissioningMassUpdate($user);
        $plan = $service->analyze($path);

        $this->assertNull($plan['fatal']);
        $this->assertCount(1, $plan['updates']);
        $this->assertSame([], $plan['errors']);
        $this->assertSame('Colonna strana', $plan['ignoredColumns'][0]['heading']);

        $labels = array_column($plan['updates'][0]['changes'], 'label');
        $this->assertSame(['Stato', 'Permesso Ente', 'Data IL', 'Pagata NE'], $labels);
        $this->assertSame('Da Lavorare', $record->fresh()->status, 'L\'analisi non deve scrivere');

        $this->assertSame(1, $service->apply($plan));

        $record->refresh();
        $this->assertSame('Fine Lavori', $record->status);
        $this->assertSame('Nuovo', $record->permesso_ente);
        $this->assertSame('Da tenere', $record->note_permesso, 'Cella vuota: il valore resta');
        $this->assertSame('2026-03-15', $record->data_il->format('Y-m-d'));
        $this->assertTrue($record->pagata_ne);
    }

    public function test_excel_dates_and_lookups_by_name_are_resolved(): void
    {
        $user = $this->importer();
        $company = Company::create(['name' => 'Impresa Rossi']);
        $record = Decommissioning::create(['clli' => 'ROMA01', 'status' => 'Da Lavorare']);

        $path = $this->xlsx([
            ['CLLI', 'Impresa', 'Data FL'],
            ['ROMA01', 'impresa rossi', 46100],
        ]);

        $service = new DecommissioningMassUpdate($user);
        $service->apply($service->analyze($path));

        $record->refresh();
        $this->assertSame($company->id, $record->company_id);
        // 46100 è il numero seriale Excel del 19/03/2026
        $this->assertSame('2026-03-19', $record->data_fl->format('Y-m-d'));
    }

    public function test_invalid_rows_are_reported_and_skipped(): void
    {
        $user = $this->importer(finance: true);
        Decommissioning::create(['clli' => 'OK01', 'status' => 'Da Lavorare']);
        Decommissioning::create(['clli' => 'TWIN', 'status' => 'Da Lavorare']);
        Decommissioning::create(['clli' => 'twin ', 'status' => 'Da Lavorare']);
        Decommissioning::create(['clli' => 'BAD01', 'status' => 'Da Lavorare']);
        Decommissioning::create(['clli' => 'REP01', 'status' => 'Da Lavorare']);

        $path = $this->xlsx([
            ['CLLI', 'Stato', 'Val', 'Data IL', 'Impresa'],
            ['OK01', 'Sospeso', null, null, null],
            ['MISSING', 'Sospeso', null, null, null],
            ['TWIN', 'Sospeso', null, null, null],
            ['BAD01', 'Boh', 'forse', '31/02/2026', 'Nessuna'],
            ['REP01', 'Sospeso', null, null, null],
            ['rep01', 'Annullato', null, null, null],
            [null, 'Sospeso', null, null, null],
        ]);

        $plan = (new DecommissioningMassUpdate($user))->analyze($path);

        $this->assertSame(['OK01'], array_column($plan['updates'], 'clli'));

        $errors = collect($plan['errors'])->keyBy('row');
        $this->assertStringContainsString('non trovato', $errors[3]['messages'][0]);
        $this->assertStringContainsString('Più decommissioning', $errors[4]['messages'][0]);
        $this->assertCount(4, $errors[5]['messages'], 'Stato, Val, Data IL e Impresa non validi');
        $this->assertStringContainsString('ripetuto', $errors[6]['messages'][0]);
        $this->assertStringContainsString('ripetuto', $errors[7]['messages'][0]);
        $this->assertSame(['CLLI mancante'], $errors[8]['messages']);
    }

    public function test_quantity_change_recalculates_amounts_from_rates_unless_given(): void
    {
        $user = $this->importer(finance: true);
        $company = Company::create(['name' => 'Impresa Tariffe']);
        CompanyDecommissioningRate::create(['company_id' => $company->id, 'item_index' => 1, 'prog_price' => 10, 'ne_price' => 15]);
        CompanyDecommissioningRate::create(['company_id' => $company->id, 'item_index' => 2, 'prog_price' => 20, 'ne_price' => 30]);

        $record = Decommissioning::create([
            'clli' => 'CALC01',
            'status' => 'Da Lavorare',
            'company_id' => $company->id,
            'qty_1' => 1,
            'prog_amount_1' => 10,
            'ne_amount_1' => 15,
        ]);

        $path = $this->xlsx([
            ['CLLI', CompanyDecommissioningRate::ITEM_LABELS[1], CompanyDecommissioningRate::ITEM_LABELS[2], 'NE 2.0', 'Tot NE'],
            ['CALC01', 3, 2, 100, 999999],
        ]);

        $service = new DecommissioningMassUpdate($user);
        $plan = $service->analyze($path);

        $this->assertSame('Tot NE', $plan['ignoredColumns'][0]['heading']);
        $service->apply($plan);

        $record->refresh();
        $this->assertEquals(30, $record->prog_amount_1);
        $this->assertEquals(45, $record->ne_amount_1);
        $this->assertEquals(40, $record->prog_amount_2);
        $this->assertEquals(100, $record->ne_amount_2, 'Importo esplicito nel file');
        $this->assertEquals(70, $record->tot_prog);
        $this->assertEquals(145, $record->tot_ne);
        $this->assertEquals(75, $record->agio);
    }

    public function test_without_finance_permission_economic_columns_are_ignored_and_hidden(): void
    {
        $user = $this->importer(finance: false);
        $company = Company::create(['name' => 'Impresa Tariffe']);
        CompanyDecommissioningRate::create(['company_id' => $company->id, 'item_index' => 1, 'prog_price' => 10, 'ne_price' => 15]);

        $record = Decommissioning::create([
            'clli' => 'NOFIN',
            'status' => 'Da Lavorare',
            'company_id' => $company->id,
            'val' => false,
        ]);

        $path = $this->xlsx([
            ['CLLI', CompanyDecommissioningRate::ITEM_LABELS[1], 'Val', 'NE 1.0'],
            ['NOFIN', 2, 'SI', 5000],
        ]);

        $service = new DecommissioningMassUpdate($user);
        $plan = $service->analyze($path);

        $this->assertSame(['Val', 'NE 1.0'], array_column($plan['ignoredColumns'], 'heading'));
        $this->assertTrue($plan['updates'][0]['hiddenChanges']);
        $this->assertSame([CompanyDecommissioningRate::ITEM_LABELS[1]], array_column($plan['updates'][0]['changes'], 'label'));

        $service->apply($plan);

        $record->refresh();
        $this->assertFalse($record->val);
        $this->assertEquals(30, $record->ne_amount_1, 'Ricalcolato dalle tariffe, non preso dal file');
        $this->assertEquals(30, $record->tot_ne);
    }

    public function test_restricted_user_only_updates_own_records(): void
    {
        $user = $this->importer(viewAll: false);
        Decommissioning::create(['clli' => 'MINE', 'status' => 'Da Lavorare', 'progettista_id' => $user->id]);
        $other = Decommissioning::create(['clli' => 'OTHER', 'status' => 'Da Lavorare']);

        $path = $this->xlsx([
            ['CLLI', 'Stato'],
            ['MINE', 'Sospeso'],
            ['OTHER', 'Sospeso'],
        ]);

        $plan = (new DecommissioningMassUpdate($user))->analyze($path);

        $this->assertSame(['MINE'], array_column($plan['updates'], 'clli'));
        $this->assertSame('CLLI non trovato', $plan['errors'][0]['messages'][0]);
        $this->assertSame('Da Lavorare', $other->fresh()->status);
    }

    public function test_reimporting_an_unchanged_export_changes_nothing(): void
    {
        $user = $this->importer(finance: true);
        $designer = User::factory()->create();
        $designer->assignRole('Deco');
        $company = Company::create(['name' => 'Impresa Export']);

        Decommissioning::create([
            'clli' => 'ROUND01',
            'company_id' => $company->id,
            'progettista_id' => $designer->id,
            'status' => 'In Lavorazione',
            'permesso_ente' => 'Ente',
            'fl_permesso' => '2026-02-10',
            'data_il' => '2026-03-01',
            'data_fl' => '2026-03-31',
            'qty_1' => 2,
            'prog_amount_1' => 12.5,
            'ne_amount_1' => 20.25,
            'tot_prog' => 12.5,
            'tot_ne' => 20.25,
            'agio' => 7.75,
            'pagata_prog' => true,
            'pagata_ne' => false,
            'val' => true,
        ]);

        $export = new \App\Exports\DecommissioningExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $user);
        $path = tempnam(sys_get_temp_dir(), 'deco').'.xlsx';
        $this->files[] = $path;
        file_put_contents($path, \Maatwebsite\Excel\Facades\Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX));

        $plan = (new DecommissioningMassUpdate($user))->analyze($path);

        $this->assertNull($plan['fatal']);
        $this->assertSame([], $plan['errors']);
        $this->assertSame([], $plan['updates']);
        $this->assertSame(1, $plan['unchanged']);
    }

    public function test_missing_clli_column_is_fatal(): void
    {
        $path = $this->xlsx([['Codice', 'Stato'], ['X', 'Sospeso']]);

        $plan = (new DecommissioningMassUpdate($this->importer()))->analyze($path);

        $this->assertStringContainsString('CLLI', $plan['fatal']);
    }

    public function test_livewire_flow_previews_then_applies_on_confirmation(): void
    {
        $user = $this->importer();
        $record = Decommissioning::create(['clli' => 'LW01', 'status' => 'Da Lavorare']);

        $component = Livewire::actingAs($user)
            ->test(DecommissioningImport::class)
            ->set('file', $this->upload([['CLLI', 'Stato'], ['LW01', 'Sospeso']]))
            ->call('analyze')
            ->assertSee('LW01')
            ->assertSee('Sospeso')
            ->assertSet('preview.updates.0.clli', 'LW01');

        $this->assertSame('Da Lavorare', $record->fresh()->status);
        $this->assertArrayNotHasKey('payload', $component->get('preview')['updates'][0]);

        $component->call('apply')->assertSet('resultMessage', '1 decommissioning aggiornato.');

        $this->assertSame('Sospeso', $record->fresh()->status);
    }

    public function test_livewire_refuses_to_apply_when_data_changed_after_preview(): void
    {
        $user = $this->importer();
        $record = Decommissioning::create(['clli' => 'RACE', 'status' => 'Da Lavorare', 'permesso_ente' => 'A']);

        $component = Livewire::actingAs($user)
            ->test(DecommissioningImport::class)
            ->set('file', $this->upload([['CLLI', 'Permesso Ente'], ['RACE', 'B']]))
            ->call('analyze');

        $record->update(['permesso_ente' => 'C']);

        $component->call('apply')
            ->assertSet('resultMessage', null)
            ->assertSee('I dati sono cambiati');

        $this->assertSame('C', $record->fresh()->permesso_ente);
    }

    public function test_import_requires_dedicated_permission(): void
    {
        $editor = $this->userWithPermissions([P::DECO_ACCESS, P::DECO_VIEW_ALL, P::DECO_EDIT]);

        $this->actingAs($editor)->get(route('decommissionings.import'))->assertForbidden();
        Livewire::actingAs($editor)->test(DecommissioningImport::class)->assertForbidden();

        $this->actingAs($this->importer())->get(route('decommissionings.import'))->assertOk();
    }

    public function test_import_implies_edit_permission_in_catalog(): void
    {
        $this->assertSame(
            [P::DECO_ACCESS, P::DECO_EDIT, P::DECO_IMPORT],
            P::withImplied([P::DECO_IMPORT]),
        );
    }

    private function importer(bool $finance = false, bool $viewAll = true): User
    {
        $permissions = [P::DECO_ACCESS, P::DECO_EDIT, P::DECO_IMPORT];

        if ($viewAll) {
            $permissions[] = P::DECO_VIEW_ALL;
        }

        if ($finance) {
            array_push($permissions, P::DECO_FINANCE_VIEW, P::DECO_FINANCE_EDIT);
        }

        return $this->userWithPermissions($permissions);
    }

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'Import '.uniqid()]);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function xlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);

        $path = tempnam(sys_get_temp_dir(), 'deco').'.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        $this->files[] = $path;

        return $path;
    }

    private function upload(array $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('import.xlsx', file_get_contents($this->xlsx($rows)));
    }
}
