<?php

namespace Tests\Feature;

use App\Livewire\AdminBaseTables;
use App\Livewire\DecommissioningForm;
use App\Livewire\DecommissioningTable;
use App\Models\Central;
use App\Models\Company;
use App\Models\CompanyDecommissioningRate;
use App\Models\Comune;
use App\Models\Decommissioning;
use App\Models\Media;
use App\Models\Regione;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class DecommissioningFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_decommissioning_table_route_is_available_to_admin_and_deco_only(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($admin)
            ->get(route('decommissionings.table'))
            ->assertOk();

        $this->actingAs($deco)
            ->get(route('decommissionings.table'))
            ->assertOk();

        $this->actingAs($operator)
            ->get(route('decommissionings.table'))
            ->assertForbidden();
    }

    public function test_deco_users_have_server_side_economic_values_recalculated_from_company_decommissioning_rates(): void
    {
        $this->seed(RoleSeeder::class);
        [$regione, $comune, $central] = $this->createLookups();

        $company = Company::create(['name' => 'Impresa Tariffe']);
        CompanyDecommissioningRate::create([
            'company_id' => $company->id,
            'item_index' => 1,
            'prog_price' => 135.50,
            'ne_price' => 205.25,
        ]);
        CompanyDecommissioningRate::create([
            'company_id' => $company->id,
            'item_index' => 3,
            'prog_price' => 40.00,
            'ne_price' => 55.00,
        ]);

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($deco);

        Livewire::test(DecommissioningForm::class)
            ->set('company_id', $company->id)
            ->set('regione_id', $regione->id)
            ->set('comune_id', $comune->id)
            ->set('central_id', $central->id)
            ->set('progettista_id', $deco->id)
            ->set('qty_1', 2)
            ->set('qty_3', 1)
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $record = Decommissioning::firstOrFail();

        $this->assertSame(2, $record->qty_1);
        $this->assertSame(1, $record->qty_3);
        $this->assertSame('271.00', $record->prog_amount_1);
        $this->assertSame('40.00', $record->prog_amount_3);
        $this->assertSame('410.50', $record->ne_amount_1);
        $this->assertSame('55.00', $record->ne_amount_3);
        $this->assertSame('311.00', $record->tot_prog);
        $this->assertSame('465.50', $record->tot_ne);
        $this->assertSame('154.50', $record->agio);
        $this->assertFalse($record->pagata_prog);
        $this->assertFalse($record->pagata_ne);
    }

    public function test_admin_can_save_company_decommissioning_rate_from_base_tables(): void
    {
        $company = Company::create(['name' => 'Impresa Deco']);

        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'CompanyDecommissioningRate')
            ->set("decommissioningRateValues.{$company->id}.2.prog_price", '88,50')
            ->set("decommissioningRateValues.{$company->id}.2.ne_price", '120.75')
            ->call('saveDecommissioningRate', $company->id, 2);

        $this->assertDatabaseHas('company_decommissioning_rates', [
            'company_id' => $company->id,
            'item_index' => 2,
            'prog_price' => 88.50,
            'ne_price' => 120.75,
        ]);
    }

    public function test_base_table_central_changes_are_searchable_from_decommissioning_form(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Central::create([
            'central' => 'OLD-CENTRAL',
            'region' => 'Lazio',
        ]);

        $this->actingAs($admin);

        // Il form cerca le centrali su /lookups/centrals: niente cache da invalidare
        $this->getJson(route('lookups.show', ['type' => 'centrals', 'q' => 'CENTRAL']))
            ->assertOk()
            ->assertJsonFragment(['text' => 'OLD-CENTRAL'])
            ->assertJsonMissing(['text' => 'NEW-CENTRAL']);

        Livewire::test(AdminBaseTables::class)
            ->set('formData.central', 'NEW-CENTRAL')
            ->set('formData.region', 'Lazio')
            ->call('saveRecord');

        $this->getJson(route('lookups.show', ['type' => 'centrals', 'q' => 'CENTRAL']))
            ->assertJsonFragment(['text' => 'NEW-CENTRAL']);
    }

    public function test_decommissioning_can_be_associated_to_a_company(): void
    {
        $this->seed(RoleSeeder::class);
        [, , $central] = $this->createLookups();

        $company = Company::create(['name' => 'Impresa Test']);

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($deco);

        Livewire::test(DecommissioningForm::class)
            ->set('company_id', $company->id)
            ->set('central_id', $central->id)
            ->set('progettista_id', $deco->id)
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $record = Decommissioning::with('company')->firstOrFail();

        $this->assertTrue($record->company->is($company));
    }

    public function test_annullato_decommissioning_status_is_available_and_red(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin);

        Livewire::test(DecommissioningForm::class)
            ->assertSee('Annullato')
            ->set('status', 'Annullato')
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $this->assertDatabaseHas('decommissionings', [
            'status' => 'Annullato',
        ]);

        Livewire::test(DecommissioningTable::class)
            ->assertSee('Annullato')
            ->assertSeeHtml('text-bg-danger');
    }

    public function test_val_field_and_column_are_admin_only(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        Decommissioning::create([
            'clli' => 'VAL-HIDDEN',
            'status' => 'Da Lavorare',
            'progettista_id' => $deco->id,
            'val' => true,
        ]);

        $this->actingAs($deco);

        Livewire::test(DecommissioningForm::class)
            ->assertDontSeeHtml('<label class="form-label">Val</label>')
            ->set('val', '1')
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $this->assertDatabaseHas('decommissionings', [
            'id' => Decommissioning::latest('id')->first()->id,
            'val' => false,
        ]);

        Livewire::test(DecommissioningTable::class)
            ->assertSee('VAL-HIDDEN')
            ->assertDontSee('Vero');

        $this->actingAs($admin);

        Livewire::test(DecommissioningForm::class)
            ->assertSeeHtml('<label class="form-label">Val</label>');

        Livewire::test(DecommissioningTable::class)
            ->assertSee('Val')
            ->assertSee('VAL-HIDDEN')
            ->assertSee('Vero');
    }

    public function test_non_admin_users_see_only_decommissionings_assigned_to_them(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $otherDeco = User::factory()->create();
        $otherDeco->assignRole('Deco');

        Decommissioning::create([
            'clli' => 'DECO-ASSIGNED',
            'status' => 'Da Lavorare',
            'progettista_id' => $deco->id,
        ]);
        Decommissioning::create([
            'clli' => 'DECO-OTHER',
            'status' => 'Da Lavorare',
            'progettista_id' => $otherDeco->id,
        ]);
        Decommissioning::create([
            'clli' => 'DECO-UNASSIGNED',
            'status' => 'Da Lavorare',
        ]);

        $this->actingAs($deco);

        Livewire::test(DecommissioningTable::class)
            ->assertSee('DECO-ASSIGNED')
            ->assertDontSee('DECO-OTHER')
            ->assertDontSee('DECO-UNASSIGNED');

        $this->actingAs($admin);

        Livewire::test(DecommissioningTable::class)
            ->assertSee('DECO-ASSIGNED')
            ->assertSee('DECO-OTHER')
            ->assertSee('DECO-UNASSIGNED');
    }

    public function test_non_admin_users_cannot_open_or_edit_decommissionings_not_assigned_to_them(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $otherDeco = User::factory()->create();
        $otherDeco->assignRole('Deco');

        $assigned = Decommissioning::create([
            'clli' => 'DECO-MINE',
            'status' => 'Da Lavorare',
            'progettista_id' => $deco->id,
        ]);
        $foreign = Decommissioning::create([
            'clli' => 'DECO-FOREIGN',
            'status' => 'Da Lavorare',
            'progettista_id' => $otherDeco->id,
        ]);

        $this->actingAs($deco);

        $this->get(route('editDecommissioning', $assigned->id))->assertOk();
        $this->get(route('editDecommissioning', $foreign->id))->assertForbidden();

        Livewire::test(DecommissioningForm::class)
            ->call('viewDecommissioning', $foreign->id)
            ->assertForbidden();

        Livewire::test(DecommissioningForm::class)
            ->call('editDecommissioning', $foreign->id)
            ->assertForbidden();

        Livewire::test(DecommissioningForm::class)
            ->call('editDecommissioning', $assigned->id)
            ->set('decommissioningId', $foreign->id)
            ->set('clli', 'TAMPERED')
            ->call('save')
            ->assertForbidden();

        $this->assertSame('DECO-FOREIGN', $foreign->fresh()->clli);

        $this->actingAs($admin);

        $this->get(route('editDecommissioning', $foreign->id))->assertOk();
    }

    public function test_only_admin_can_delete_decommissionings(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $decommissioning = Decommissioning::create([
            'clli' => 'DECO-DELETE',
            'status' => 'Da Lavorare',
            'progettista_id' => $deco->id,
        ]);

        $this->actingAs($deco)
            ->delete(route('deleteDecommissioning', $decommissioning))
            ->assertForbidden();

        $this->assertDatabaseHas('decommissionings', ['id' => $decommissioning->id]);

        $this->actingAs($admin)
            ->delete(route('deleteDecommissioning', $decommissioning))
            ->assertRedirect();

        $this->assertDatabaseMissing('decommissionings', ['id' => $decommissioning->id]);
    }

    public function test_non_admin_users_are_assigned_as_progettista_by_default(): void
    {
        $this->seed(RoleSeeder::class);

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($deco);

        Livewire::test(DecommissioningForm::class)
            ->assertSet('progettista_id', $deco->id)
            ->set('progettista_id', '')
            ->set('clli', 'DECO-DEFAULT')
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $this->assertDatabaseHas('decommissionings', [
            'clli' => 'DECO-DEFAULT',
            'progettista_id' => $deco->id,
        ]);
    }

    public function test_decommissioning_media_download_is_limited_to_assigned_progettista(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $otherDeco = User::factory()->create();
        $otherDeco->assignRole('Deco');

        $decommissioning = Decommissioning::create([
            'clli' => 'DECO-MEDIA',
            'status' => 'Da Lavorare',
            'progettista_id' => $deco->id,
        ]);

        Storage::disk('local')->put('decommissioning_media/allegato.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => Decommissioning::class,
            'mediable_id' => $decommissioning->id,
            'file_path' => 'decommissioning_media/allegato.txt',
            'file_name' => 'allegato.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->actingAs($deco)
            ->get(route('media.download', $media))
            ->assertOk()
            ->assertDownload('allegato.txt');

        $this->actingAs($otherDeco)
            ->get(route('media.download', $media))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('media.download', $media))
            ->assertOk();
    }

    public function test_decommissioning_table_filters_by_company(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $matchingCompany = Company::create(['name' => 'Impresa Visibile']);
        $otherCompany = Company::create(['name' => 'Impresa Nascosta']);

        Decommissioning::create([
            'company_id' => $matchingCompany->id,
            'clli' => 'DECO-COMPANY-MATCH',
            'status' => 'Da Lavorare',
        ]);
        Decommissioning::create([
            'company_id' => $otherCompany->id,
            'clli' => 'DECO-COMPANY-OTHER',
            'status' => 'Da Lavorare',
        ]);

        $this->actingAs($admin);

        Livewire::test(DecommissioningTable::class)
            ->call('setFilter', 'company_id', (string) $matchingCompany->id)
            ->assertSee('DECO-COMPANY-MATCH')
            ->assertDontSee('DECO-COMPANY-OTHER');
    }

    public function test_admin_can_override_economic_values_and_flags(): void
    {
        $this->seed(RoleSeeder::class);
        [, , $central] = $this->createLookups();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($admin);

        Livewire::test(DecommissioningForm::class)
            ->set('central_id', $central->id)
            ->set('progettista_id', $deco->id)
            ->set('qty_1', 2)
            ->set('prog_amount_1', 999)
            ->set('ne_amount_1', 1111)
            ->set('pagata_prog', '1')
            ->set('pagata_ne', '1')
            ->call('save')
            ->assertRedirect(route('decommissionings.table'));

        $record = Decommissioning::firstOrFail();

        $this->assertSame('999.00', $record->prog_amount_1);
        $this->assertSame('1111.00', $record->ne_amount_1);
        $this->assertSame('999.00', $record->tot_prog);
        $this->assertSame('1111.00', $record->tot_ne);
        $this->assertSame('112.00', $record->agio);
        $this->assertTrue($record->pagata_prog);
        $this->assertTrue($record->pagata_ne);
    }

    private function createLookups(): array
    {
        $regione = Regione::create(['nome' => 'Lazio']);

        $comune = Comune::create([
            'comune_progressive' => '001',
            'code' => 'H501',
            'name' => 'Roma',
            'regione_id' => $regione->id,
        ]);

        $central = Central::create([
            'central' => 'RM-CENT-01',
            'region' => 'Lazio',
        ]);

        return [$regione, $comune, $central];
    }
}
