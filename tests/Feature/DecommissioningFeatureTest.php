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
use App\Models\Regione;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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

    public function test_base_table_central_changes_refresh_decommissioning_form_options(): void
    {
        $this->seed(RoleSeeder::class);

        Central::create([
            'central' => 'OLD-CENTRAL',
            'region' => 'Lazio',
        ]);

        Livewire::test(DecommissioningForm::class)
            ->assertSee('OLD-CENTRAL')
            ->assertDontSee('NEW-CENTRAL');

        $this->assertTrue(Cache::has('deco_centrali_list'));

        Livewire::test(AdminBaseTables::class)
            ->set('formData.central', 'NEW-CENTRAL')
            ->set('formData.region', 'Lazio')
            ->call('saveRecord');

        Livewire::test(DecommissioningForm::class)
            ->assertSee('NEW-CENTRAL');
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
