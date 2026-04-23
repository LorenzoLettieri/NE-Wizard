<?php

namespace Tests\Feature;

use App\Livewire\AdminBaseTables;
use App\Livewire\WorkForm;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Database\Seeders\RoleSeeder;
use Database\Seeders\WorkPhaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\Support\UsesMysqlTestDatabase;
use Tests\TestCase;

class WorkPhaseFeatureTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_work_phase_seeder_is_idempotent(): void
    {
        $this->seed(WorkPhaseSeeder::class);
        $this->seed(WorkPhaseSeeder::class);

        $this->assertSame(16, WorkPhase::count());
        $this->assertDatabaseHas('work_phases', ['name' => 'FASE 1']);
        $this->assertDatabaseHas('work_phases', ['name' => 'NET FO 5G']);
    }

    public function test_sync_phases_dry_run_does_not_update_works(): void
    {
        $this->seed(WorkPhaseSeeder::class);

        $work = Work::create(['phase' => 'STEP 1']);

        Artisan::call('works:sync-phases');
        $output = Artisan::output();

        $this->assertNull($work->fresh()->work_phase_id);
        $this->assertStringContainsString('Dry-run', $output);
        $this->assertStringContainsString('STEP 1', $output);
    }

    public function test_sync_phases_apply_maps_canonical_and_legacy_values(): void
    {
        $this->seed(WorkPhaseSeeder::class);

        $stepOne = Work::create(['phase' => 'STEP 1']);
        $canonical = Work::create(['phase' => 'MODIFICA']);

        Artisan::call('works:sync-phases', ['--apply' => true, '--force' => true]);

        $this->assertSame(
            WorkPhase::where('name', 'FASE 1')->value('id'),
            $stepOne->fresh()->work_phase_id
        );
        $this->assertSame(
            WorkPhase::where('name', 'MODIFICA')->value('id'),
            $canonical->fresh()->work_phase_id
        );
    }

    public function test_sync_phases_reports_unknown_values_without_modifying_them(): void
    {
        $this->seed(WorkPhaseSeeder::class);

        $work = Work::create(['phase' => 'VALORE SCONOSCIUTO']);

        Artisan::call('works:sync-phases', ['--apply' => true, '--force' => true]);

        $this->assertNull($work->fresh()->work_phase_id);
        $this->assertStringContainsString('VALORE SCONOSCIUTO', Artisan::output());
    }

    public function test_sync_phases_can_update_soft_deleted_works_when_requested(): void
    {
        $this->seed(WorkPhaseSeeder::class);

        $work = Work::create(['phase' => 'STEP 2']);
        $work->delete();

        Artisan::call('works:sync-phases', [
            '--apply' => true,
            '--force' => true,
            '--include-trashed' => true,
        ]);

        $this->assertSame(
            WorkPhase::where('name', 'FASE 2')->value('id'),
            Work::withTrashed()->find($work->id)->work_phase_id
        );
    }

    public function test_admin_cannot_delete_a_work_phase_used_by_works(): void
    {
        $phase = WorkPhase::create(['name' => 'FASE TEST']);
        Work::create(['work_phase_id' => $phase->id]);

        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'WorkPhase')
            ->call('deleteRecord', $phase->id);

        $this->assertDatabaseHas('work_phases', [
            'id' => $phase->id,
            'name' => 'FASE TEST',
        ]);
    }

    public function test_admin_can_create_work_with_work_phase_id(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        $this->actingAs($admin);

        Livewire::test(WorkForm::class)
            ->set('status', 'Da Lavorare')
            ->set('network', 'NTW-PHASE')
            ->set('daphne', false)
            ->set('operator_id', $operator->id)
            ->set('work_phase_id', $phase->id)
            ->call('store');

        $this->assertDatabaseHas('works', [
            'network' => 'NTW-PHASE',
            'work_phase_id' => $phase->id,
        ]);
    }
}
