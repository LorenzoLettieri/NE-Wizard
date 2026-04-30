<?php

namespace Tests\Feature;

use App\Exports\WorksExport;
use App\Livewire\EditWork;
use App\Livewire\OperatorStats;
use App\Livewire\OperatorTable;
use App\Livewire\ViewWork;
use App\Livewire\WorkForm;
use App\Livewire\WorkEdit;
use App\Livewire\WorksTable;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkSuspension;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Tests\Support\UsesMysqlTestDatabase;

class WorkSuspensionFeatureTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_suspend_action_creates_one_open_suspension_and_is_idempotent(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-03-30 08:00:00', 'UTC'),
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 10:15:00', 'UTC'));

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->call('suspendWork', $work->id)
            ->call('suspendWork', $work->id);

        $work->refresh();

        $this->assertSame('Sospeso', $work->status);
        $this->assertCount(1, $work->workSuspensions);
        $this->assertNull($work->workSuspensions->first()->ended_at);
        $this->assertSame(
            '2026-03-31 10:15:00',
            $work->workSuspensions->first()->started_at->format('Y-m-d H:i:s')
        );
    }

    public function test_resume_closes_the_open_suspension_without_creating_duplicates(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-03-30 08:00:00', 'UTC'),
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 09:00:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 11:45:00', 'UTC'));

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->call('unsuspendWork', $work->id);

        $work->refresh();
        $suspension = $work->workSuspensions()->firstOrFail();

        $this->assertSame('In Lavorazione', $work->status);
        $this->assertSame(1, $work->workSuspensions()->count());
        $this->assertSame('2026-03-31 11:45:00', $suspension->ended_at->format('Y-m-d H:i:s'));
    }

    public function test_delivery_closes_any_open_suspension_before_setting_delivery_date(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 09:30:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 12:00:00', 'UTC'));

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->call('deliveryWork', $work->id);

        $work->refresh();
        $suspension = $work->workSuspensions()->firstOrFail();

        $this->assertSame('Consegnato', $work->status);
        $this->assertSame('2026-03-31 12:00:00', $work->delivery_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 12:00:00', $suspension->ended_at->format('Y-m-d H:i:s'));
    }

    public function test_effective_processing_time_uses_only_overlap_inside_the_work_window(): void
    {
        $work = Work::create([
            'status' => 'Consegnato',
            'acception_date' => Carbon::parse('2026-03-31 10:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 18:00:00', 'UTC'),
        ]);

        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-03-31 11:00:00', 'UTC'),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 13:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-03-31 14:30:00', 'UTC'),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 17:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-03-31 20:00:00', 'UTC'),
        ]);

        $work->load('workSuspensions');

        $this->assertSame(12600, $work->total_suspension_seconds);
        $this->assertSame(16200, $work->effective_processing_seconds);
        $this->assertSame('4h 30m', $work->effective_processing_label);
    }

    public function test_admin_edit_rejects_overlapping_suspension_intervals(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Consegnato',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 18:00:00', 'UTC'),
        ]);

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('suspensions', [
                [
                    'id' => null,
                    'started_at' => '2026-03-31T10:00',
                    'ended_at' => '2026-03-31T11:00',
                ],
                [
                    'id' => null,
                    'started_at' => '2026-03-31T10:30',
                    'ended_at' => '2026-03-31T12:00',
                ],
            ])
            ->call('update')
            ->assertHasErrors(['suspensions.1.started_at']);
    }

    public function test_admin_edit_rejects_intervals_where_end_precedes_start(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Consegnato',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 18:00:00', 'UTC'),
        ]);

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('suspensions', [
                [
                    'id' => null,
                    'started_at' => '2026-03-31T12:00',
                    'ended_at' => '2026-03-31T11:00',
                ],
            ])
            ->call('update')
            ->assertHasErrors(['suspensions.0.ended_at']);
    }

    public function test_supervisor_edit_to_suspended_creates_open_suspension(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'daphne' => false,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 12:15:00', 'UTC'));

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('status', 'Sospeso')
            ->set('daphne', false)
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();
        $suspension = $work->workSuspensions()->first();

        $this->assertSame('Sospeso', $work->status);
        $this->assertNotNull($suspension);
        $this->assertSame('2026-03-31 12:15:00', $suspension->started_at->format('Y-m-d H:i:s'));
        $this->assertNull($suspension->ended_at);
    }

    public function test_supervisor_edit_to_fine_lavori_closes_open_suspension_and_sets_delivery_date(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'daphne' => false,
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 10:00:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 16:30:00', 'UTC'));

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('status', 'Fine Lavori')
            ->set('daphne', false)
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();
        $suspension = $work->workSuspensions()->firstOrFail();

        $this->assertSame('Fine Lavori', $work->status);
        $this->assertSame('2026-03-31 16:30:00', $work->delivery_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31', $work->completion_date->format('Y-m-d'));
        $this->assertSame('2026-03-31 16:30:00', $suspension->ended_at->format('Y-m-d H:i:s'));
    }

    public function test_supervisor_edit_to_final_status_does_not_override_existing_delivery_date(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
            'daphne' => false,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 17:00:00', 'UTC'));

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('status', 'Fine Lavori')
            ->set('daphne', false)
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();

        $this->assertSame('Fine Lavori', $work->status);
        $this->assertSame('2026-03-31 14:00:00', $work->delivery_date->format('Y-m-d H:i:s'));
    }

    public function test_supervisor_reopening_from_fine_lavori_clears_completion_and_delivery_dates(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Fine Lavori',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
            'completion_date' => Carbon::parse('2026-03-31 15:00:00', 'UTC'),
            'daphne' => false,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 17:00:00', 'UTC'));

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('status', 'In Lavorazione')
            ->set('daphne', false)
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();

        $this->assertSame('In Lavorazione', $work->status);
        $this->assertSame('2026-03-31 08:00:00', $work->acception_date->format('Y-m-d H:i:s'));
        $this->assertNull($work->delivery_date);
        $this->assertNull($work->completion_date);
    }

    public function test_supervisor_edit_to_da_lavorare_resets_workflow_dates_and_closes_open_suspension(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
            'completion_date' => Carbon::parse('2026-03-31 15:00:00', 'UTC'),
            'daphne' => false,
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 10:00:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 16:45:00', 'UTC'));

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('status', 'Da Lavorare')
            ->set('daphne', false)
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();
        $suspension = $work->workSuspensions()->firstOrFail();

        $this->assertSame('Da Lavorare', $work->status);
        $this->assertNull($work->acception_date);
        $this->assertNull($work->delivery_date);
        $this->assertNull($work->completion_date);
        $this->assertSame('2026-03-31 16:45:00', $suspension->ended_at->format('Y-m-d H:i:s'));
    }

    public function test_duplicate_work_resets_workflow_dates_and_does_not_copy_suspensions(): void
    {
        $work = Work::create([
            'status' => 'Consegnato',
            'acception_date' => Carbon::parse('2026-03-30 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'completion_date' => Carbon::parse('2026-03-31 09:00:00', 'UTC'),
            'expected_delivery_date' => Carbon::parse('2026-04-03', 'UTC'),
            'suspension_history' => 'Legacy notes',
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-30 10:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-03-30 11:00:00', 'UTC'),
        ]);

        Livewire::test(ViewWork::class)
            ->call('duplicateWork', $work->id)
            ->assertRedirect();

        $duplicate = Work::query()->latest('id')->firstOrFail();

        $this->assertNotSame($work->id, $duplicate->id);
        $this->assertSame('Da Lavorare', $duplicate->status);
        $this->assertNull($duplicate->acception_date);
        $this->assertNull($duplicate->delivery_date);
        $this->assertNull($duplicate->completion_date);
        $this->assertNull($duplicate->expected_delivery_date);
        $this->assertNull($duplicate->suspension_history);
        $this->assertSame(0, $duplicate->workSuspensions()->count());
    }

    public function test_admin_end_work_action_uses_aligned_workflow_dates_and_suspensions(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'daphne' => false,
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 11:00:00', 'UTC'),
        ]);

        Carbon::setTestNow(Carbon::parse('2026-03-31 18:10:00', 'UTC'));

        $this->actingAs($admin);

        Livewire::test(ViewWork::class)
            ->call('endWork', $work->id);

        $work->refresh();
        $suspension = $work->workSuspensions()->firstOrFail();

        $this->assertSame('Fine Lavori', $work->status);
        $this->assertSame('2026-03-31 18:10:00', $work->delivery_date->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31', $work->completion_date->format('Y-m-d'));
        $this->assertSame('2026-03-31 18:10:00', $suspension->ended_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_can_create_work_with_expected_delivery_date(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($admin);

        Livewire::test(WorkForm::class)
            ->set('status', 'Da Lavorare')
            ->set('network', 'NTW-001')
            ->set('daphne', false)
            ->set('operator_id', $operator->id)
            ->set('expected_delivery_date', '2026-04-15')
            ->call('store');

        $this->assertDatabaseHas('works', [
            'network' => 'NTW-001',
            'expected_delivery_date' => '2026-04-15',
        ]);
    }

    public function test_supervisor_can_update_expected_delivery_date(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'expected_delivery_date' => null,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(WorkEdit::class, ['work' => $work])
            ->set('expected_delivery_date', '2026-04-20')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'expected_delivery_date' => '2026-04-20',
        ]);
    }

    public function test_operator_cannot_force_update_expected_delivery_date_via_livewire_request(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'expected_delivery_date' => '2026-04-10',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('expected_delivery_date', '2026-04-22')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'expected_delivery_date' => '2026-04-10',
        ]);
    }

    public function test_works_table_renders_expected_delivery_date_column_and_value(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        Work::create([
            'status' => 'Da Lavorare',
            'expected_delivery_date' => '2026-04-15',
        ]);

        $this->actingAs($supervisor);

        Livewire::test(WorksTable::class)
            ->assertSee('Data prevista consegna')
            ->assertSee('15/04/2026');
    }

    public function test_operator_table_renders_expected_delivery_date_column_and_value(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'expected_delivery_date' => '2026-04-15',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        Livewire::test(OperatorTable::class)
            ->assertSee('Data prevista consegna')
            ->assertSee('15/04/2026');
    }

    public function test_view_work_shows_expected_delivery_date(): void
    {
        $work = Work::create([
            'status' => 'Da Lavorare',
            'expected_delivery_date' => '2026-04-15',
        ]);

        Livewire::test(ViewWork::class)
            ->call('viewWork', $work->id)
            ->assertSee('Data prevista consegna')
            ->assertSee('15/04/2026');
    }

    public function test_export_and_operator_stats_use_effective_processing_time(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Consegnato',
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
            'unit_rate' => 12.50,
            'accounting_amount' => 50.00,
            'created_at' => Carbon::parse('2026-03-31 07:30:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => Carbon::parse('2026-03-31 07:45:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-31 07:45:00', 'UTC'),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-03-31 10:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-03-31 12:00:00', 'UTC'),
        ]);

        $work->load(['users', 'workSuspensions']);

        $export = new WorksExport('created_at', '2026-03-31', '2026-03-31');

        $this->assertContains('Tempo effettivo di lavorazione', $export->headings());
        $this->assertContains('Importo contabilizzato', $export->headings());
        $this->assertContains('Tariffa unitaria', $export->headings());
        $this->assertSame(50.0, $export->map($work)[10]);
        $this->assertSame(12.5, $export->map($work)[11]);
        $this->assertSame('4h', $export->map($work)[20]);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-03-01')
            ->set('endDate', '2026-03-31')
            ->assertSee('Mario Rossi')
            ->assertSee('4 h');
    }
}
