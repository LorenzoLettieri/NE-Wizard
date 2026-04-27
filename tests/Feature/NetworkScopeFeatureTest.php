<?php

namespace Tests\Feature;

use App\Livewire\AdminBaseTables;
use App\Livewire\OperatorTable;
use App\Livewire\WorkForm;
use App\Livewire\WorksTable;
use App\Models\NetworkScope;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\NetworkScopeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\Support\UsesMysqlTestDatabase;
use Tests\TestCase;

class NetworkScopeFeatureTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_network_scope_seeder_is_idempotent(): void
    {
        $this->seed(NetworkScopeSeeder::class);
        $this->seed(NetworkScopeSeeder::class);

        $this->assertSame(11, NetworkScope::count());
        $this->assertDatabaseHas('network_scopes', ['name' => 'FTTH']);
        $this->assertDatabaseHas('network_scopes', ['name' => 'SUB-LOOP']);
    }

    public function test_sync_network_scopes_dry_run_does_not_update_works(): void
    {
        $this->seed(NetworkScopeSeeder::class);

        $work = Work::create(['ntw_scope' => 'FTTH']);

        Artisan::call('works:sync-network-scopes');
        $output = Artisan::output();

        $this->assertNull($work->fresh()->network_scope_id);
        $this->assertStringContainsString('Dry-run', $output);
    }

    public function test_sync_network_scopes_apply_maps_known_values(): void
    {
        $this->seed(NetworkScopeSeeder::class);

        $ftth = Work::create(['ntw_scope' => 'FTTH']);
        $ngan = Work::create(['ntw_scope' => 'NGAN']);

        Artisan::call('works:sync-network-scopes', ['--apply' => true, '--force' => true]);

        $this->assertSame(
            NetworkScope::where('name', 'FTTH')->value('id'),
            $ftth->fresh()->network_scope_id
        );
        $this->assertSame(
            NetworkScope::where('name', 'NGAN')->value('id'),
            $ngan->fresh()->network_scope_id
        );
    }

    public function test_sync_network_scopes_reports_unknown_values_without_modifying_them(): void
    {
        $this->seed(NetworkScopeSeeder::class);

        $work = Work::create(['ntw_scope' => 'VALORE SCONOSCIUTO']);

        Artisan::call('works:sync-network-scopes', ['--apply' => true, '--force' => true]);

        $this->assertNull($work->fresh()->network_scope_id);
        $this->assertStringContainsString('VALORE SCONOSCIUTO', Artisan::output());
    }

    public function test_sync_network_scopes_can_update_soft_deleted_works_when_requested(): void
    {
        $this->seed(NetworkScopeSeeder::class);

        $work = Work::create(['ntw_scope' => '5G']);
        $work->delete();

        Artisan::call('works:sync-network-scopes', [
            '--apply' => true,
            '--force' => true,
            '--include-trashed' => true,
        ]);

        $this->assertSame(
            NetworkScope::where('name', '5G')->value('id'),
            Work::withTrashed()->find($work->id)->network_scope_id
        );
    }

    public function test_admin_cannot_delete_a_network_scope_used_by_works(): void
    {
        $scope = NetworkScope::create(['name' => 'TEST SCOPE']);
        Work::create(['network_scope_id' => $scope->id]);

        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'NetworkScope')
            ->call('deleteRecord', $scope->id);

        $this->assertDatabaseHas('network_scopes', [
            'id' => $scope->id,
            'name' => 'TEST SCOPE',
        ]);
    }

    public function test_admin_can_create_network_scope_via_admin_panel(): void
    {
        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'NetworkScope')
            ->call('openCreateModal')
            ->set('formData.name', 'NUOVO AMBITO')
            ->call('saveRecord');

        $this->assertDatabaseHas('network_scopes', ['name' => 'NUOVO AMBITO']);
    }

    public function test_admin_cannot_create_duplicate_network_scope(): void
    {
        NetworkScope::create(['name' => 'FTTH']);

        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'NetworkScope')
            ->call('openCreateModal')
            ->set('formData.name', 'FTTH')
            ->call('saveRecord')
            ->assertHasErrors(['formData.name']);
    }

    public function test_work_creation_persists_network_scope_id(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $scope = NetworkScope::create(['name' => 'FTTH']);

        $this->actingAs($admin);

        Livewire::test(WorkForm::class)
            ->set('status', 'Da Lavorare')
            ->set('network', 'NTW-SCOPE-TEST')
            ->set('daphne', false)
            ->set('operator_id', $operator->id)
            ->set('network_scope_id', $scope->id)
            ->call('store');

        $this->assertDatabaseHas('works', [
            'network' => 'NTW-SCOPE-TEST',
            'network_scope_id' => $scope->id,
            'ntw_scope' => 'FTTH',
        ]);
    }

    public function test_works_table_filters_by_network_scope_id(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $scope = NetworkScope::create(['name' => 'FTTH']);
        Work::create(['network_scope_id' => $scope->id, 'ntw_scope' => 'FTTH']);

        $this->actingAs($supervisor);

        Livewire::test(WorksTable::class)
            ->assertSee('Ambito NTW');
    }

    public function test_operator_table_filters_by_network_scope_id(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $scope = NetworkScope::create(['name' => '5G']);
        $work = Work::create(['network_scope_id' => $scope->id, 'ntw_scope' => '5G']);
        $work->users()->attach($operator->id);

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->assertSee('Ambito NTW');
    }
}
