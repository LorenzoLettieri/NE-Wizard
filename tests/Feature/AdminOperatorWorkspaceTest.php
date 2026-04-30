<?php

namespace Tests\Feature;

use App\Livewire\OperatorStats;
use App\Livewire\OperatorTable;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class AdminOperatorWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_single_operator_workspace_route(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $this->actingAs($admin)
            ->get(route('admin.operator-workspace', $operator))
            ->assertOk()
            ->assertSee('Stai visualizzando la bacheca operatore', false)
            ->assertSee('Mario Rossi');
    }

    public function test_non_admin_cannot_open_the_single_operator_workspace_route(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($supervisor)
            ->get(route('admin.operator-workspace', $operator))
            ->assertForbidden();
    }

    public function test_admin_cannot_open_the_workspace_for_a_user_without_operator_access(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $nonOperator = User::factory()->create();
        $nonOperator->assignRole('permessi ente');

        $this->actingAs($admin)
            ->get(route('admin.operator-workspace', $nonOperator))
            ->assertNotFound();
    }

    public function test_user_table_shows_admin_workspace_link_only_for_operator_capable_non_admin_users(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create(['name' => 'Admin User']);
        $admin->assignRole('admin');

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $nonOperator = User::factory()->create(['name' => 'Sara Backoffice']);
        $nonOperator->assignRole('permessi ente');

        $this->actingAs($admin)
            ->get(route('accounts-table'))
            ->assertOk()
            ->assertSee(route('admin.operator-workspace', $operator), false)
            ->assertSee('Vedi come operatore')
            ->assertDontSee(route('admin.operator-workspace', $admin), false)
            ->assertDontSee(route('admin.operator-workspace', $nonOperator), false);
    }

    public function test_admin_workspace_table_only_shows_selected_operator_works(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetOperator = User::factory()->create();
        $targetOperator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $targetWork = Work::create([
            'network' => 'TARGET-NTW',
            'status' => 'Da Lavorare',
        ]);
        $targetWork->users()->attach($targetOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherWork = Work::create([
            'network' => 'OTHER-NTW',
            'status' => 'Da Lavorare',
        ]);
        $otherWork->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorTable::class, [
            'targetOperatorId' => $targetOperator->id,
            'readOnlyMode' => true,
        ])
            ->assertSee('TARGET-NTW')
            ->assertDontSee('OTHER-NTW');
    }

    public function test_admin_workspace_table_hides_operator_mutation_actions_in_read_only_mode(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'network' => 'READ-ONLY-NTW',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorTable::class, [
            'targetOperatorId' => $operator->id,
            'readOnlyMode' => true,
        ])
            ->assertSee('Dettaglio')
            ->assertDontSee('Prendi in carico')
            ->assertDontSee('Consegna Lavorazione')
            ->assertDontSee('Modifica Operazione')
            ->assertDontSee('Sospendi Lavorazione')
            ->assertDontSee('Riprendi Lavorazione');
    }

    public function test_admin_workspace_table_rejects_mutating_actions_in_read_only_mode(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorTable::class, [
            'targetOperatorId' => $operator->id,
            'readOnlyMode' => true,
        ])
            ->call('takeWork', $work->id)
            ->assertForbidden();
    }

    public function test_operator_stats_locked_mode_returns_only_the_selected_operator_and_hides_the_selector(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetOperator = User::factory()->create(['name' => 'Mario Rossi']);
        $targetOperator->assignRole('operator');

        $otherOperator = User::factory()->create(['name' => 'Luca Bianchi']);
        $otherOperator->assignRole('operator');

        $targetWork = Work::create([
            'status' => 'Fine Lavori',
            'completion_date' => '2026-04-12',
            'accounting_amount' => 100,
        ]);
        $targetWork->users()->attach($targetOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherWork = Work::create([
            'status' => 'Fine Lavori',
            'completion_date' => '2026-04-12',
            'accounting_amount' => 200,
        ]);
        $otherWork->users()->attach($otherOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorStats::class, [
            'lockedOperatorId' => $targetOperator->id,
            'hideOperatorFilterWhenLocked' => true,
        ])
            ->set('startDate', '2026-04-01')
            ->set('endDate', '2026-04-30')
            ->assertViewHas('rows', function ($rows) use ($targetOperator) {
                return count($rows) === 1
                    && $rows[0]['operator_id'] === $targetOperator->id;
            })
            ->assertDontSee('Luca Bianchi')
            ->assertDontSee('wire:model.live="operatorId"', false);
    }
}
