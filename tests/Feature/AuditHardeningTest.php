<?php

namespace Tests\Feature;

use App\Exports\WorksExport;
use App\Livewire\PermessiEnteTable;
use App\Livewire\PermessoEnteForm;
use App\Models\Central;
use App\Models\PermessoEnte;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AuditHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->post('/register', [
            'name' => 'Sconosciuto',
            'email' => 'stranger@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertNotFound();

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'stranger@example.com']);
    }

    public function test_guests_are_redirected_to_login_on_role_only_routes(): void
    {
        $this->get(route('admin.timesheets'))->assertRedirect('/');
        $this->get(route('operator-timesheet'))->assertRedirect('/');
        $this->get(route('operator-table'))->assertRedirect('/');
    }

    public function test_editing_a_missing_user_returns_not_found(): void
    {
        $this->actingAs($this->userWithRole('admin'))
            ->get(route('editUser', ['id' => 999999]))
            ->assertNotFound();
    }

    public function test_user_edit_form_has_password_confirmation_field(): void
    {
        $target = $this->userWithRole('operator');

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('editUser', ['id' => $target->id]))
            ->assertOk()
            ->assertSee('name="password_confirmation"', false);
    }

    public function test_each_work_row_has_its_own_delete_modal(): void
    {
        $first = Work::create(['status' => 'Da Lavorare']);
        $second = Work::create(['status' => 'Da Lavorare']);

        $html = view('works.works-table-actions', ['row' => $first])->render()
            .view('works.works-table-actions', ['row' => $second])->render();

        // Prima ogni riga usava id="deleteModal": Bootstrap apriva sempre il primo e si cancellava la riga sbagliata
        $this->assertStringContainsString('id="deleteWorkModal'.$first->id.'"', $html);
        $this->assertStringContainsString('data-bs-target="#deleteWorkModal'.$second->id.'"', $html);
        $this->assertStringContainsString(route('deleteWork', ['work' => $second]), $html);
        $this->assertStringNotContainsString('id="deleteModal"', $html);
    }

    public function test_lookup_requires_authentication(): void
    {
        $this->getJson(route('lookups.show', ['type' => 'centrals', 'q' => 'Roma']))
            ->assertUnauthorized();
    }

    public function test_lookup_searches_by_name_and_resolves_ids(): void
    {
        $roma = Central::create(['central' => 'ROMA EUR', 'region' => 'Lazio']);
        Central::create(['central' => 'MILANO CENTRO', 'region' => 'Lombardia']);

        $this->actingAs($this->userWithRole('operator'));

        $this->getJson(route('lookups.show', ['type' => 'centrals', 'q' => 'roma']))
            ->assertOk()
            ->assertExactJson([['value' => (string) $roma->id, 'text' => 'ROMA EUR']]);

        $this->getJson(route('lookups.show', ['type' => 'centrals', 'q' => 'r']))
            ->assertExactJson([]);

        $this->getJson(route('lookups.show', ['type' => 'centrals']).'?ids[]='.$roma->id)
            ->assertExactJson([['value' => (string) $roma->id, 'text' => 'ROMA EUR']]);

        $this->get('/lookups/users?q=admin')->assertNotFound();
    }

    public function test_works_export_hides_accounting_columns_from_supervisors(): void
    {
        Excel::fake();

        $this->actingAs($this->userWithRole('supervisor'))
            ->get(route('exports.works', ['date_field' => 'created_at', 'start' => '2026-01-01', 'end' => '2026-12-31']))
            ->assertOk();

        Excel::assertDownloaded('works_created_at_2026-01-01_2026-12-31.xlsx', function (WorksExport $export) {
            return ! in_array('Importo contabilizzato', $export->headings(), true)
                && ! in_array('Tariffa unitaria', $export->headings(), true);
        });
    }

    public function test_works_export_keeps_accounting_columns_for_admins(): void
    {
        Excel::fake();

        $this->actingAs($this->userWithRole('admin'))
            ->get(route('exports.works', ['date_field' => 'created_at', 'start' => '2026-01-01', 'end' => '2026-12-31']))
            ->assertOk();

        Excel::assertDownloaded('works_created_at_2026-01-01_2026-12-31.xlsx', function (WorksExport $export) {
            return in_array('Importo contabilizzato', $export->headings(), true)
                && count($export->headings()) === 27;
        });
    }

    public function test_export_without_accounting_keeps_rows_aligned_with_headings(): void
    {
        $export = new WorksExport('created_at', '2026-03-31', '2026-03-31', includeAccounting: false);
        $work = new Work(['status' => 'Consegnato', 'network' => 'NTW-1', 'accounting_amount' => 50, 'unit_rate' => 10]);
        $work->setRelation('users', collect());
        $work->setRelation('workSuspensions', collect());

        $headings = $export->headings();
        $row = $export->map($work);

        $this->assertCount(count($headings), $row);
        $this->assertSame('NTW-1', $row[array_search('Network', $headings, true)]);
        $this->assertNotContains(50.0, $row);
        $this->assertArrayHasKey('T', $export->columnFormats());
        $this->assertArrayNotHasKey('V', $export->columnFormats());
    }

    public function test_legacy_consegnata_status_is_treated_as_consegnato(): void
    {
        $admin = $this->userWithRole('admin');
        $permesso = PermessoEnte::create(['progetto' => 'PE-LEGACY', 'status' => 'Consegnata']);
        PermessoEnte::create(['progetto' => 'PE-TODO', 'status' => 'Da Lavorare']);

        $this->actingAs($admin);

        // Il filtro "Consegnato" include il valore storico e l'azione Fine Lavori resta disponibile
        Livewire::test(PermessiEnteTable::class)
            ->set('filterComponents.status_filter', PermessoEnte::STATUS_DELIVERED)
            ->assertSee('PE-LEGACY')
            ->assertDontSee('PE-TODO')
            ->assertSeeHtml("Livewire.dispatch('end-permesso', {id: {$permesso->id}})");

        // Il dato non viene riscritto finché nessuno salva il record
        $this->assertSame('Consegnata', $permesso->fresh()->status);

        // Aprendo il form la select mostra il valore canonico, salvabile senza errori
        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $permesso->id)
            ->assertSet('status', PermessoEnte::STATUS_DELIVERED)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(PermessoEnte::STATUS_DELIVERED, $permesso->fresh()->status);
    }
}
