<?php

namespace Tests\Feature;

use App\Livewire\AdminBaseTables;
use App\Livewire\OperatorTable;
use App\Livewire\WorkEdit;
use App\Livewire\WorkForm;
use App\Livewire\WorksTable;
use App\Models\Company;
use App\Models\CompanyWorkPhaseRate;
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

    public function test_works_table_renders_legacy_work_phase_name(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        Work::create([
            'status' => 'Da Lavorare',
            'phase' => 'FASE LEGACY VISIBILE',
        ]);

        $this->actingAs($supervisor);

        Livewire::test(WorksTable::class)
            ->assertSee('Fase')
            ->assertSee('FASE LEGACY VISIBILE');
    }

    public function test_operator_table_renders_legacy_work_phase_name(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'phase' => 'FASE LEGACY OPERATORE',
        ]);
        $work->users()->attach($operator->id);

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->assertSee('Fase')
            ->assertSee('FASE LEGACY OPERATORE');
    }

    public function test_work_creation_persists_accounting_amount_from_company_phase_rate(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 12.50,
        ]);

        $this->actingAs($admin);

        Livewire::test(WorkForm::class)
            ->set('status', 'Da Lavorare')
            ->set('network', 'NTW-RATE')
            ->set('daphne', false)
            ->set('operator_id', $operator->id)
            ->set('company_id', $company->id)
            ->set('work_phase_id', $phase->id)
            ->set('nroe', 4)
            ->call('store');

        $this->assertDatabaseHas('works', [
            'network' => 'NTW-RATE',
            'unit_rate' => 12.50,
            'accounting_amount' => 50.00,
        ]);
    }

    public function test_work_creation_assumes_one_nroe_when_calculating_accounting_amount_with_null_nroe(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 12.50,
        ]);

        $this->actingAs($admin);

        Livewire::test(WorkForm::class)
            ->set('status', 'Da Lavorare')
            ->set('network', 'NTW-RATE-NULL-NROE')
            ->set('daphne', false)
            ->set('operator_id', $operator->id)
            ->set('company_id', $company->id)
            ->set('work_phase_id', $phase->id)
            ->set('nroe', null)
            ->call('store');

        $this->assertDatabaseHas('works', [
            'network' => 'NTW-RATE-NULL-NROE',
            'nroe' => null,
            'unit_rate' => 12.50,
            'accounting_amount' => 12.50,
        ]);
    }

    public function test_work_update_recalculates_accounting_amount(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 10.00,
        ]);

        $work = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'nroe' => 2,
            'unit_rate' => 10.00,
            'accounting_amount' => 20.00,
        ]);
        $work->users()->attach($operator->id);

        $this->actingAs($admin);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('operator_id', [$operator->id])
            ->set('nroe', 3)
            ->call('update');

        $work->refresh();

        $this->assertSame('10.00', $work->unit_rate);
        $this->assertSame('30.00', $work->accounting_amount);
    }

    public function test_admin_can_save_company_work_phase_rate_from_base_tables(): void
    {
        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        Livewire::test(AdminBaseTables::class)
            ->call('setTab', 'CompanyWorkPhaseRate')
            ->set("rateValues.{$company->id}.{$phase->id}", '18.75')
            ->call('saveRate', $company->id, $phase->id);

        $this->assertDatabaseHas('company_work_phase_rates', [
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 18.75,
        ]);
    }

    public function test_backfill_accounting_amounts_dry_run_does_not_update_works(): void
    {
        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 12.50,
        ]);

        $work = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'nroe' => 4,
        ]);

        Artisan::call('works:backfill-accounting-amounts');
        $output = Artisan::output();

        $work->refresh();

        $this->assertNull($work->unit_rate);
        $this->assertNull($work->accounting_amount);
        $this->assertStringContainsString('Dry-run completed', $output);
        $this->assertStringContainsString('eligible', $output);
    }

    public function test_backfill_accounting_amounts_apply_fills_missing_values_without_overwriting_existing_ones(): void
    {
        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 10.00,
        ]);

        $missingAccounting = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'nroe' => 3,
        ]);

        $existingAccounting = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'nroe' => 5,
            'unit_rate' => 99.00,
            'accounting_amount' => 495.00,
        ]);

        Artisan::call('works:backfill-accounting-amounts', [
            '--apply' => true,
            '--force' => true,
        ]);
        $output = Artisan::output();

        $missingAccounting->refresh();
        $existingAccounting->refresh();

        $this->assertSame('10.00', $missingAccounting->unit_rate);
        $this->assertSame('30.00', $missingAccounting->accounting_amount);
        $this->assertSame('99.00', $existingAccounting->unit_rate);
        $this->assertSame('495.00', $existingAccounting->accounting_amount);
        $this->assertStringContainsString('Apply mode completed', $output);
    }

    public function test_backfill_accounting_amounts_assumes_one_nroe_when_nroe_is_null(): void
    {
        $company = Company::create(['name' => 'SIRTI']);
        $phase = WorkPhase::create(['name' => 'FASE TEST']);

        CompanyWorkPhaseRate::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'unit_price' => 7.50,
        ]);

        $work = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'nroe' => null,
        ]);

        Artisan::call('works:backfill-accounting-amounts', [
            '--apply' => true,
            '--force' => true,
        ]);

        $work->refresh();

        $this->assertSame('7.50', $work->unit_rate);
        $this->assertSame('7.50', $work->accounting_amount);
    }

    public function test_backfill_accounting_amounts_skips_works_without_required_rate_data(): void
    {
        $company = Company::create(['name' => 'SIRTI']);
        $unratedPhase = WorkPhase::create(['name' => 'FASE SENZA TARIFFA']);

        $missingRate = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $unratedPhase->id,
            'nroe' => 2,
        ]);

        Artisan::call('works:backfill-accounting-amounts', [
            '--apply' => true,
            '--force' => true,
        ]);
        $output = Artisan::output();

        $this->assertNull($missingRate->fresh()->accounting_amount);
        $this->assertStringContainsString('missing_rate', $output);
    }
}
