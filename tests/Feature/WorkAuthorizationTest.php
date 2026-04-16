<?php

namespace Tests\Feature;

use App\Livewire\EditWork;
use App\Livewire\OperatorTable;
use App\Livewire\ViewWork;
use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Features\SupportTesting\Testable;
use Tests\TestCase;

class WorkAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_operator_cannot_suspend_work_not_assigned_to_him(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'notes' => 'Should stay untouched',
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(OperatorTable::class)
            ->call('suspendWork', $work->id));

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'In Lavorazione',
        ]);
        $this->assertSame(0, $work->fresh()->workSuspensions()->count());
    }

    public function test_operator_cannot_take_work_not_assigned_to_him(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'acception_date' => null,
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(OperatorTable::class)
            ->call('takeWork', $work->id));

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'Da Lavorare',
            'acception_date' => null,
        ]);
    }

    public function test_operator_cannot_deliver_work_not_assigned_to_him(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Sospeso',
            'delivery_date' => null,
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-04-14 08:00:00', 'UTC'),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(OperatorTable::class)
            ->call('deliveryWork', $work->id));

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'Sospeso',
            'delivery_date' => null,
        ]);
        $this->assertNull($work->fresh()->workSuspensions()->first()->ended_at);
    }

    public function test_operator_cannot_unsuspend_work_not_assigned_to_him(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Sospeso',
            'acception_date' => Carbon::parse('2026-04-14 07:00:00', 'UTC'),
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-04-14 08:00:00', 'UTC'),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(OperatorTable::class)
            ->call('unsuspendWork', $work->id));

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'Sospeso',
        ]);
        $this->assertNull($work->fresh()->workSuspensions()->first()->ended_at);
    }

    public function test_operator_cannot_view_work_not_assigned_to_him(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Private notes',
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(ViewWork::class)
            ->call('viewWork', $work->id));
    }

    public function test_operator_cannot_load_another_operators_work_through_edit_work(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Private notes',
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(EditWork::class)
            ->call('editWork', $work->id));
    }

    public function test_operator_cannot_update_another_operators_work_through_edit_work(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Original notes',
        ]);
        $work->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $component = Livewire::test(EditWork::class, ['work' => $work->fresh()]);
        $component->set('notes', 'Tampered notes');

        $this->assertUnauthorizedAction(fn () => $component->call('update'));

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'notes' => 'Original notes',
        ]);
    }

    public function test_supervisor_can_update_work_not_assigned_to_him_through_edit_work(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Original notes',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($supervisor);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('notes', 'Supervisor notes')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'notes' => 'Supervisor notes',
        ]);
    }

    public function test_admin_can_complete_work_through_view_work(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'completion_date' => null,
        ]);

        Carbon::setTestNow(Carbon::parse('2026-04-14 09:30:00', 'UTC'));
        $this->actingAs($admin);

        Livewire::test(ViewWork::class)
            ->call('endWork', $work->id);

        $this->assertDatabaseHas('works', [
            'id' => $work->id,
            'status' => 'Fine Lavori',
            'completion_date' => '2026-04-14',
        ]);
    }

    public function test_operator_cannot_duplicate_work_through_view_work(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'network' => 'NTW-001',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($operator);

        $this->assertUnauthorizedAction(fn () => Livewire::test(ViewWork::class)
            ->call('duplicateWork', $work->id));

        $this->assertSame(1, Work::count());
    }

    private function assertUnauthorizedAction(callable $callback): void
    {
        /** @var Testable $component */
        $component = $callback();

        $component->assertForbidden();
    }
}
