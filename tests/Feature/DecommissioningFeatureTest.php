<?php

namespace Tests\Feature;

use App\Livewire\DecommissioningForm;
use App\Models\Central;
use App\Models\Comune;
use App\Models\Decommissioning;
use App\Models\Regione;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_deco_users_have_server_side_economic_values_recalculated_from_quantities(): void
    {
        $this->seed(RoleSeeder::class);
        [$regione, $comune, $central] = $this->createLookups();

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($deco);

        Livewire::test(DecommissioningForm::class)
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
        $this->assertSame('240.00', $record->prog_amount_1);
        $this->assertSame('30.00', $record->prog_amount_3);
        $this->assertSame('370.00', $record->ne_amount_1);
        $this->assertSame('49.00', $record->ne_amount_3);
        $this->assertSame('270.00', $record->tot_prog);
        $this->assertSame('419.00', $record->tot_ne);
        $this->assertSame('149.00', $record->agio);
        $this->assertFalse($record->pagata_prog);
        $this->assertFalse($record->pagata_ne);
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
