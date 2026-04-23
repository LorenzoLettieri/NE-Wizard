<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class OperatorStats extends Component
{
    private const MONTHLY_TARGET = 3500.0;

    public $startDate;

    public $endDate;

    public $operatorId = '';

    public $status = '';

    public $companyId = '';

    public $workPhaseId = '';

    public $ntwScope = '';

    public function mount()
    {
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }

    public function render()
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();
        $canViewEconomicReport = auth()->check() && auth()->user()->hasRole('admin');

        $operators = User::permission('get works')
            ->when($this->operatorId !== '', fn (Builder $query) => $query->whereKey($this->operatorId))
            ->orderBy('name')
            ->get();

        $rows = $operators
            ->map(fn (User $operator): array => $this->buildOperatorRow($operator, $startDate, $endDate))
            ->values()
            ->all();

        $economicSummary = $this->buildEconomicSummary($rows);

        return view('livewire.operator-stats', [
            'rows' => $rows,
            'economicSummary' => $economicSummary,
            'canViewEconomicReport' => $canViewEconomicReport,
            'monthlyTarget' => self::MONTHLY_TARGET,
            'operatorOptions' => User::permission('get works')->orderBy('name')->get(['id', 'name']),
            'companyOptions' => Company::orderBy('name')->get(['id', 'name']),
            'workPhaseOptions' => WorkPhase::orderBy('name')->get(['id', 'name']),
            'ntwScopeOptions' => $this->ntwScopeOptions(),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function resetFilters(): void
    {
        $this->operatorId = '';
        $this->status = '';
        $this->companyId = '';
        $this->workPhaseId = '';
        $this->ntwScope = '';
        $this->startDate = Carbon::now()->startOfMonth()->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }

    private function buildOperatorRow(User $operator, Carbon $startDate, Carbon $endDate): array
    {
        $assignedWorks = $operator->works()
            ->whereBetween('user_work.created_at', [$startDate, $endDate])
            ->with('workSuspensions')
            ->tap(fn (Builder $query) => $this->applyWorkFilters($query))
            ->get();

        $earnedWorks = $operator->works()
            ->whereBetween('works.completion_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->tap(fn (Builder $query) => $this->applyWorkFilters($query))
            ->get();

        $averageProcessingSeconds = $assignedWorks
            ->whereIn('status', ['Consegnato', 'Fine Lavori'])
            ->filter(fn (Work $work) => $work->effective_processing_seconds !== null)
            ->map(fn (Work $work) => $work->effective_processing_seconds)
            ->avg();

        $earnedAmount = round((float) $earnedWorks
            ->filter(fn (Work $work) => $work->accounting_amount !== null)
            ->sum(fn (Work $work) => (float) $work->accounting_amount), 2);

        $targetPercentage = self::MONTHLY_TARGET > 0
            ? round(($earnedAmount / self::MONTHLY_TARGET) * 100, 1)
            : 0.0;

        return [
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'assigned_count' => $assignedWorks->count(),
            'to_do_count' => $assignedWorks->where('status', 'Da Lavorare')->count(),
            'in_progress_count' => $assignedWorks->where('status', 'In Lavorazione')->count(),
            'suspended_count' => $assignedWorks->where('status', 'Sospeso')->count(),
            'delivered_count' => $assignedWorks->where('status', 'Consegnato')->count(),
            'completed_count' => $assignedWorks->where('status', 'Fine Lavori')->count(),
            'ko_count' => $assignedWorks->where('status', 'KO')->count(),
            'average_processing_label' => $averageProcessingSeconds === null
                ? '-'
                : Work::formatDuration((int) round($averageProcessingSeconds)),
            'nroe_total' => (int) $earnedWorks->sum(fn (Work $work) => (int) ($work->nroe ?? 0)),
            'earned_amount' => $earnedAmount,
            'earned_works_count' => $earnedWorks->filter(fn (Work $work) => $work->accounting_amount !== null)->count(),
            'missing_amount_count' => $earnedWorks->filter(fn (Work $work) => $work->accounting_amount === null)->count(),
            'target_amount' => self::MONTHLY_TARGET,
            'target_percentage' => $targetPercentage,
            'target_bar_width' => min(100, $targetPercentage),
            'target_class' => $this->targetClass($targetPercentage),
        ];
    }

    private function applyWorkFilters(Builder $query): void
    {
        $query
            ->when($this->status !== '', fn (Builder $query) => $query->where('works.status', $this->status))
            ->when($this->companyId !== '', fn (Builder $query) => $query->where('works.company_id', $this->companyId))
            ->when($this->workPhaseId !== '', fn (Builder $query) => $query->where('works.work_phase_id', $this->workPhaseId))
            ->when($this->ntwScope !== '', fn (Builder $query) => $query->where('works.ntw_scope', $this->ntwScope));
    }

    private function buildEconomicSummary(array $rows): array
    {
        $totalEarned = round(array_sum(array_column($rows, 'earned_amount')), 2);
        $totalTarget = count($rows) * self::MONTHLY_TARGET;

        return [
            'total_earned' => $totalEarned,
            'total_target' => $totalTarget,
            'target_percentage' => $totalTarget > 0 ? round(($totalEarned / $totalTarget) * 100, 1) : 0.0,
            'earned_works_count' => array_sum(array_column($rows, 'earned_works_count')),
            'missing_amount_count' => array_sum(array_column($rows, 'missing_amount_count')),
        ];
    }

    private function targetClass(float $percentage): string
    {
        if ($percentage >= 100) {
            return 'bg-success';
        }

        if ($percentage >= 60) {
            return 'bg-warning';
        }

        return 'bg-danger';
    }

    private function statusOptions(): array
    {
        return [
            'Da Lavorare',
            'In Lavorazione',
            'Sospeso',
            'Attesa Fine Lavori',
            'KO',
            'Consegnato',
            'Fine Lavori',
        ];
    }

    private function ntwScopeOptions(): array
    {
        return [
            'FTTH',
            'FTTH PTE',
            'FTTH PNRR',
            '5G',
            'REACTIVE',
            'INCREMENTALE',
            'DESATURAZIONE',
            'NGAN',
            'GIUNZIONE',
            'SUB-LOOP',
            'Altro',
        ];
    }
}
