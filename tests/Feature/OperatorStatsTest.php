<?php

namespace Tests\Feature;

use App\Livewire\OperatorStats;
use App\Models\Company;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class OperatorStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_counts_use_assignment_date_and_earnings_use_completion_date(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $company = Company::create(['name' => 'Open Fiber']);
        $phase = WorkPhase::create(['name' => 'FASE 1']);

        $assignedInPeriod = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'status' => 'In Lavorazione',
            'ntw_scope' => 'FTTH',
            'nroe' => 3,
            'accounting_amount' => 200,
            'completion_date' => '2026-04-02',
        ]);
        $assignedInPeriod->users()->attach($operator->id, [
            'created_at' => Carbon::parse('2026-03-10 09:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-10 09:00:00', 'UTC'),
        ]);

        $completedInPeriod = Work::create([
            'company_id' => $company->id,
            'work_phase_id' => $phase->id,
            'status' => 'Fine Lavori',
            'ntw_scope' => 'FTTH',
            'nroe' => 4,
            'accounting_amount' => 120,
            'completion_date' => '2026-03-20',
        ]);
        $completedInPeriod->users()->attach($operator->id, [
            'created_at' => Carbon::parse('2026-02-25 09:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-02-25 09:00:00', 'UTC'),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-03-01')
            ->set('endDate', '2026-03-31')
            ->assertViewHas('rows', function ($rows) {
                $row = collect($rows)->firstWhere('operator_name', 'Mario Rossi');

                return $row
                    && $row['assigned_count'] === 1
                    && $row['in_progress_count'] === 1
                    && $row['earned_amount'] === 120.0
                    && $row['earned_works_count'] === 1
                    && $row['nroe_total'] === 4;
            });
    }

    public function test_multi_operator_work_awards_full_amount_to_each_operator_and_tracks_missing_amounts(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $firstOperator = User::factory()->create(['name' => 'Anna Verdi']);
        $secondOperator = User::factory()->create(['name' => 'Luca Bianchi']);
        $firstOperator->assignRole('operator');
        $secondOperator->assignRole('operator');

        $paidWork = Work::create([
            'status' => 'Fine Lavori',
            'completion_date' => '2026-03-15',
            'accounting_amount' => 350,
            'nroe' => 2,
        ]);
        $paidWork->users()->attach([
            $firstOperator->id => ['created_at' => Carbon::parse('2026-03-01', 'UTC'), 'updated_at' => Carbon::parse('2026-03-01', 'UTC')],
            $secondOperator->id => ['created_at' => Carbon::parse('2026-03-01', 'UTC'), 'updated_at' => Carbon::parse('2026-03-01', 'UTC')],
        ]);

        $missingAmountWork = Work::create([
            'status' => 'Fine Lavori',
            'completion_date' => '2026-03-18',
            'accounting_amount' => null,
            'nroe' => 5,
        ]);
        $missingAmountWork->users()->attach($firstOperator->id, [
            'created_at' => Carbon::parse('2026-03-02', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-02', 'UTC'),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-03-01')
            ->set('endDate', '2026-03-31')
            ->assertViewHas('rows', function ($rows) {
                $rows = collect($rows)->keyBy('operator_name');

                return $rows['Anna Verdi']['earned_amount'] === 350.0
                    && $rows['Luca Bianchi']['earned_amount'] === 350.0
                    && $rows['Anna Verdi']['missing_amount_count'] === 1
                    && $rows['Luca Bianchi']['missing_amount_count'] === 0;
            })
            ->assertViewHas('economicSummary', function ($summary) {
                return $summary['total_earned'] === 700.0
                    && $summary['missing_amount_count'] === 1;
            });
    }

    public function test_economic_report_is_hidden_from_supervisors(): void
    {
        $this->seed(RoleSeeder::class);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Fine Lavori',
            'completion_date' => '2026-03-15',
            'accounting_amount' => 500,
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => Carbon::parse('2026-03-01', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-01', 'UTC'),
        ]);

        $this->actingAs($supervisor);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-03-01')
            ->set('endDate', '2026-03-31')
            ->assertViewHas('canViewEconomicReport', false)
            ->assertDontSee('Contachilometri')
            ->assertDontSee('Importo maturato')
            ->assertDontSee('500,00');
    }

    public function test_essential_filters_apply_to_operator_stats(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $matchingOperator = User::factory()->create(['name' => 'Mario Rossi']);
        $otherOperator = User::factory()->create(['name' => 'Luca Bianchi']);
        $matchingOperator->assignRole('operator');
        $otherOperator->assignRole('operator');

        $matchingCompany = Company::create(['name' => 'Open Fiber']);
        $otherCompany = Company::create(['name' => 'Altro Cliente']);
        $matchingPhase = WorkPhase::create(['name' => 'FASE 1']);
        $otherPhase = WorkPhase::create(['name' => 'MODIFICA']);

        $matchingWork = Work::create([
            'company_id' => $matchingCompany->id,
            'work_phase_id' => $matchingPhase->id,
            'status' => 'Fine Lavori',
            'ntw_scope' => 'FTTH',
            'completion_date' => '2026-03-10',
            'accounting_amount' => 90,
        ]);
        $matchingWork->users()->attach($matchingOperator->id, [
            'created_at' => Carbon::parse('2026-03-10', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-10', 'UTC'),
        ]);

        $otherWork = Work::create([
            'company_id' => $otherCompany->id,
            'work_phase_id' => $otherPhase->id,
            'status' => 'Consegnato',
            'ntw_scope' => '5G',
            'completion_date' => '2026-03-10',
            'accounting_amount' => 150,
        ]);
        $otherWork->users()->attach($otherOperator->id, [
            'created_at' => Carbon::parse('2026-03-10', 'UTC'),
            'updated_at' => Carbon::parse('2026-03-10', 'UTC'),
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-03-01')
            ->set('endDate', '2026-03-31')
            ->set('operatorId', (string) $matchingOperator->id)
            ->set('status', 'Fine Lavori')
            ->set('companyId', (string) $matchingCompany->id)
            ->set('workPhaseId', (string) $matchingPhase->id)
            ->set('ntwScope', 'FTTH')
            ->assertViewHas('rows', function ($rows) {
                return count($rows) === 1
                    && $rows[0]['operator_name'] === 'Mario Rossi'
                    && $rows[0]['assigned_count'] === 1
                    && $rows[0]['earned_amount'] === 90.0;
            });
    }

    public function test_operator_activity_report_distinguishes_shift_time_breaks_and_work_time(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $operator = User::factory()->create(['name' => 'Mario Rossi']);
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-04-10 08:30:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-04-10 12:30:00', 'UTC'),
        ]);

        $work->users()->attach($operator->id, [
            'created_at' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'updated_at' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
        ]);

        $work->workSuspensions()->create([
            'started_at' => Carbon::parse('2026-04-10 09:00:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-04-10 09:30:00', 'UTC'),
        ]);

        Timesheet::create([
            'user_id' => $operator->id,
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'break_start' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
            'break_end' => Carbon::parse('2026-04-10 10:30:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
            'overtime_hours' => 1,
        ]);

        $this->actingAs($admin);

        Livewire::test(OperatorStats::class)
            ->set('startDate', '2026-04-10')
            ->set('endDate', '2026-04-10')
            ->assertViewHas('rows', function ($rows) {
                $row = collect($rows)->firstWhere('operator_name', 'Mario Rossi');

                return $row
                    && $row['presence_label'] === '8h 30m'
                    && $row['break_label'] === '30m'
                    && $row['active_work_label'] === '3h'
                    && $row['suspension_label'] === '30m'
                    && $row['overtime_label'] === '1h';
            });
    }
}
