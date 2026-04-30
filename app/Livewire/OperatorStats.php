<?php

namespace App\Livewire;

use App\Domain\OperatorActivity\OperatorActivityBuilder;
use App\Domain\OperatorActivity\OperatorActivityChartFormatter;
use App\Models\Company;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
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

    public $viewMode = 'day';

    public $selectedDay = '';

    public $selectedWeekStart = '';

    public ?int $lockedOperatorId = null;

    public bool $hideOperatorFilterWhenLocked = false;

    public function mount(?int $lockedOperatorId = null, bool $hideOperatorFilterWhenLocked = false)
    {
        $this->lockedOperatorId = $lockedOperatorId;
        $this->hideOperatorFilterWhenLocked = $hideOperatorFilterWhenLocked;
        $now = Carbon::now('Europe/Rome');
        $this->startDate = $now->copy()->startOfMonth()->toDateString();
        $this->endDate = $now->toDateString();
        $this->selectedDay = $now->toDateString();
        $this->selectedWeekStart = $now->copy()->startOfWeek()->toDateString();

        if ($this->lockedOperatorId !== null) {
            $this->operatorId = (string) $this->lockedOperatorId;
        }
    }

    public function render()
    {
        $t0 = microtime(true);

        [$startDate, $endDate] = $this->resolveReportRange($this->startDate, $this->endDate);
        [$viewWindowStart, $viewWindowEnd, $dayOptions, $weekOptions] = $this->resolveTimelineWindow($startDate, $endDate);
        $canViewEconomicReport = auth()->check() && auth()->user()->hasRole('admin');

        $selectedOperatorId = $this->lockedOperatorId !== null
            ? (string) $this->lockedOperatorId
            : $this->operatorId;

        $operators = User::permission('get works')
            ->when($selectedOperatorId !== '', fn (Builder $query) => $query->whereKey($selectedOperatorId))
            ->orderBy('name')
            ->get();

        Log::debug('render: setup ' . round((microtime(true) - $t0) * 1000) . 'ms');
        $t0 = microtime(true);

        $rows = $this->buildAllRows($operators, $startDate, $endDate);

        Log::debug('render: buildAllRows ' . round((microtime(true) - $t0) * 1000) . 'ms, operators=' . count($rows));
        $t0 = microtime(true);

        $timelineData = $this->formatTimelineData($rows, $viewWindowStart, $viewWindowEnd);

        Log::debug('render: formatTimelineData ' . round((microtime(true) - $t0) * 1000) . 'ms, series=' . count($timelineData));
        $t0 = microtime(true);

        $timelineConfig = [
            'mode' => $this->viewMode,
            'min' => $viewWindowStart->getTimestamp() * 1000,
            'max' => $viewWindowEnd->getTimestamp() * 1000,
        ];

        $this->dispatch('timeline-data', series: $timelineData, config: $timelineConfig);

        $view = view('livewire.operator-stats', [
            'rows' => $rows,
            'economicSummary' => $this->buildEconomicSummary($rows),
            'canViewEconomicReport' => $canViewEconomicReport,
            'monthlyTarget' => self::MONTHLY_TARGET,
            'operatorOptions' => User::permission('get works')->orderBy('name')->get(['id', 'name']),
            'hideOperatorFilter' => $this->hideOperatorFilterWhenLocked && $this->lockedOperatorId !== null,
            'companyOptions' => Company::orderBy('name')->get(['id', 'name']),
            'workPhaseOptions' => WorkPhase::orderBy('name')->get(['id', 'name']),
            'ntwScopeOptions' => $this->ntwScopeOptions(),
            'statusOptions' => $this->statusOptions(),
            'timelineData' => $timelineData,

            'dayOptions' => $dayOptions,
            'weekOptions' => $weekOptions,
            'timelineModeOptions' => [
                ['value' => 'day', 'label' => 'Giornaliera'],
                ['value' => 'week', 'label' => 'Settimanale'],
            ],
            'timelineWindowLabel' => $this->timelineWindowLabel($viewWindowStart, $viewWindowEnd),
            'timelineConfig' => $timelineConfig,
        ]);

        Log::debug('render: view() ' . round((microtime(true) - $t0) * 1000) . 'ms');

        return $view;
    }

    private function formatTimelineData(array $rows, Carbon $viewWindowStart, Carbon $viewWindowEnd): array
    {
        $seriesByName = [];

        foreach ($rows as $row) {
            foreach ($row['timeline']($viewWindowStart, $viewWindowEnd) as $series) {
                $seriesByName[$series['name']] ??= [
                    'name' => $series['name'],
                    'color' => $series['color'] ?? null,
                    'data' => [],
                ];

                array_push($seriesByName[$series['name']]['data'], ...$series['data']);
            }
        }

        return array_values($seriesByName);
    }

    public function resetFilters(): void
    {
        $this->operatorId = $this->lockedOperatorId !== null ? (string) $this->lockedOperatorId : '';
        $this->status = '';
        $this->companyId = '';
        $this->workPhaseId = '';
        $this->ntwScope = '';
        $now = Carbon::now('Europe/Rome');
        $this->startDate = $now->copy()->startOfMonth()->toDateString();
        $this->endDate = $now->toDateString();
        $this->viewMode = 'day';
        $this->selectedDay = $now->toDateString();
        $this->selectedWeekStart = $now->copy()->startOfWeek()->toDateString();
    }

    private function resolveReportRange(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate, 'Europe/Rome')->startOfDay()->setTimezone('UTC'),
            Carbon::parse($endDate, 'Europe/Rome')->endOfDay()->setTimezone('UTC'),
        ];
    }

    private function buildAllRows(\Illuminate\Support\Collection $operators, Carbon $startDate, Carbon $endDate): array
    {
        if ($operators->isEmpty()) {
            return [];
        }

        $operatorIds = $operators->pluck('id')->all();
        $startDateLocal = $startDate->copy()->timezone('Europe/Rome')->toDateString();
        $endDateLocal = $endDate->copy()->timezone('Europe/Rome')->toDateString();

        $t = microtime(true);
            $allAssignedWorks = Work::query()
                ->join('user_work', 'works.id', '=', 'user_work.work_id')
                ->whereIn('user_work.user_id', $operatorIds)
                ->where(function (Builder $query) use ($startDate, $endDate) {
                    $query->whereBetween('user_work.created_at', [$startDate, $endDate])
                        ->orWhere(function (Builder $nested) use ($startDate, $endDate) {
                            $nested->whereNotNull('works.acception_date')
                                ->where('works.acception_date', '<=', $endDate)
                                ->where(function (Builder $window) use ($startDate) {
                                    $window->whereNull('works.delivery_date')
                                        ->orWhere('works.delivery_date', '>=', $startDate);
                                });
                        });
                })
                ->tap(fn (Builder $query) => $this->applyWorkFilters($query))
                ->select('works.*', 'user_work.user_id as _operator_id')
                ->with(['workSuspensions', 'workPhase', 'statusHistory'])
                ->get()
                ->groupBy('_operator_id');

            $allEarnedWorks = Work::query()
                ->join('user_work', 'works.id', '=', 'user_work.work_id')
                ->whereIn('user_work.user_id', $operatorIds)
                ->whereBetween('works.completion_date', [$startDateLocal, $endDateLocal])
                ->tap(fn (Builder $query) => $this->applyWorkFilters($query))
                ->select('works.*', 'user_work.user_id as _operator_id')
                ->get()
                ->groupBy('_operator_id');

            $allTimesheets = Timesheet::query()
                ->whereIn('user_id', $operatorIds)
                ->whereBetween('date', [$startDateLocal, $endDateLocal])
                ->get()
                ->groupBy('user_id');

            Log::debug('queries: ' . round((microtime(true) - $t) * 1000) . 'ms');
            $t = microtime(true);

            $rowsData = [];

            foreach ($operators as $operator) {
                $assignedWorks = $allAssignedWorks->get($operator->id, collect());
                $earnedWorks = $allEarnedWorks->get($operator->id, collect());
                $timesheets = $allTimesheets->get($operator->id, collect());

                $activity = app(OperatorActivityBuilder::class)->build(
                    $operator,
                    $assignedWorks,
                    $timesheets,
                    $startDate,
                    $endDate,
                );

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

                // Pre-format full-range timeline as plain int arrays — no Carbon objects, fast to serialize
                $fullRangeSeries = app(OperatorActivityChartFormatter::class)
                    ->forOperator($operator, $activity, $startDate, $endDate);

                $rowsData[] = [
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
                    'presence_seconds' => $activity->presenceSeconds(),
                    'presence_label' => Work::formatDuration($activity->presenceSeconds()),
                    'break_seconds' => $activity->breakSeconds(),
                    'break_label' => Work::formatDuration($activity->breakSeconds()),
                    'active_work_seconds' => $activity->activeWorkSeconds(),
                    'active_work_label' => Work::formatDuration($activity->activeWorkSeconds()),
                    'suspension_seconds' => $activity->suspensionSeconds(),
                    'suspension_label' => Work::formatDuration($activity->suspensionSeconds()),
                    'overtime_seconds' => $activity->overtimeSeconds(),
                    'overtime_label' => Work::formatDuration($activity->overtimeSeconds()),
                    'leave_seconds' => $activity->leaveSeconds(),
                    'leave_label' => Work::formatDuration($activity->leaveSeconds()),
                    'utilization_percentage' => $activity->utilizationPercentage(),
                    'daily_breakdown' => $activity->dailyBreakdown(),
                    'weekly_summary' => $activity->aggregateBy('week'),
                    'monthly_summary' => $activity->aggregateBy('month'),
                    'nroe_total' => (int) $earnedWorks->sum(fn (Work $work) => (int) ($work->nroe ?? 0)),
                    'earned_amount' => $earnedAmount,
                    'earned_works_count' => $earnedWorks->filter(fn (Work $work) => $work->accounting_amount !== null)->count(),
                    'missing_amount_count' => $earnedWorks->filter(fn (Work $work) => $work->accounting_amount === null)->count(),
                    'target_amount' => self::MONTHLY_TARGET,
                    'target_percentage' => $targetPercentage,
                    'target_bar_width' => min(100, $targetPercentage),
                    'target_class' => $this->targetClass($targetPercentage),
                    '_series' => $fullRangeSeries,
                ];
            }

            Log::debug('computation: ' . round((microtime(true) - $t) * 1000) . 'ms');

        foreach ($rowsData as &$rowData) {
            $series = $rowData['_series'];
            unset($rowData['_series']);
            $rowData['timeline'] = function (Carbon $windowStart, Carbon $windowEnd) use ($series): array {
                $wsMs = $windowStart->getTimestamp() * 1000;
                $weMs = $windowEnd->getTimestamp() * 1000;
                $result = [];
                foreach ($series as $s) {
                    if (($s['name'] ?? '') === 'Sospensione') {
                        continue;
                    }

                    $clippedData = [];
                    foreach ($s['data'] as $point) {
                        [$startMs, $endMs] = $point['y'];
                        if ($startMs >= $weMs || $endMs <= $wsMs) {
                            continue;
                        }
                        $clippedStart = max($startMs, $wsMs);
                        $clippedEnd = min($endMs, $weMs);
                        $point['y'] = [$clippedStart, $clippedEnd];
                        $point['meta']['duration_label'] = Work::formatDuration((int) (($clippedEnd - $clippedStart) / 1000));
                        $clippedData[] = $point;
                    }
                    if (! empty($clippedData)) {
                        $result[] = ['name' => $s['name'], 'color' => $s['color'], 'data' => $clippedData];
                    }
                }

                return $result;
            };
        }
        unset($rowData);

        return $rowsData;
    }

    private function applyWorkFilters(Builder $query): void
    {
        $query
            ->when($this->status !== '', fn (Builder $builder) => $builder->where('works.status', $this->status))
            ->when($this->companyId !== '', fn (Builder $builder) => $builder->where('works.company_id', $this->companyId))
            ->when($this->workPhaseId !== '', fn (Builder $builder) => $builder->where('works.work_phase_id', $this->workPhaseId))
            ->when($this->ntwScope !== '', fn (Builder $builder) => $builder->where('works.ntw_scope', $this->ntwScope));
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

    private function resolveTimelineWindow(Carbon $startDate, Carbon $endDate): array
    {
        $dayOptions = [];
        $weekOptions = [];
        $rangeStart = $startDate->copy()->timezone('Europe/Rome')->startOfDay();
        $rangeEnd = $endDate->copy()->timezone('Europe/Rome')->endOfDay();
        $cursor = $rangeStart->copy();

        while ($cursor->lte($rangeEnd)) {
            $dayOptions[] = [
                'value' => $cursor->toDateString(),
                'label' => $cursor->format('d/m/Y'),
            ];
            $cursor->addDay();
        }

        $weekCursor = $rangeStart->copy()->startOfWeek();
        $weekEndBoundary = $rangeEnd->copy()->endOfWeek();
        while ($weekCursor->lte($weekEndBoundary)) {
            $weekOptions[] = [
                'value' => $weekCursor->toDateString(),
                'label' => sprintf(
                    '%s - %s',
                    $weekCursor->format('d/m'),
                    $weekCursor->copy()->endOfWeek()->format('d/m'),
                ),
            ];
            $weekCursor->addWeek();
        }

        $dayValues = array_column($dayOptions, 'value');
        $weekValues = array_column($weekOptions, 'value');

        if (! in_array($this->selectedDay, $dayValues, true)) {
            $this->selectedDay = $rangeEnd->copy()->toDateString();
        }

        if (! in_array($this->selectedWeekStart, $weekValues, true)) {
            $this->selectedWeekStart = $rangeEnd->copy()->startOfWeek()->toDateString();
        }

        if ($this->viewMode === 'week') {
            $weekStart = Carbon::parse($this->selectedWeekStart, 'Europe/Rome')
                ->startOfWeek()
                ->startOfDay()
                ->setTimezone('UTC');
            $weekEnd = Carbon::parse($this->selectedWeekStart, 'Europe/Rome')
                ->endOfWeek()
                ->endOfDay()
                ->setTimezone('UTC');

            return [$weekStart, $weekEnd, $dayOptions, $weekOptions];
        }

        $day = Carbon::parse($this->selectedDay, 'Europe/Rome');

        return [
            $day->copy()->setTime(7, 0, 0)->setTimezone('UTC'),
            $day->copy()->setTime(19, 0, 0)->setTimezone('UTC'),
            $dayOptions,
            $weekOptions,
        ];
    }

    private function timelineWindowLabel(Carbon $viewWindowStart, Carbon $viewWindowEnd): string
    {
        if ($this->viewMode === 'week') {
            return sprintf(
                'Settimana %s - %s',
                $viewWindowStart->copy()->timezone('Europe/Rome')->format('d/m/Y'),
                $viewWindowEnd->copy()->timezone('Europe/Rome')->format('d/m/Y'),
            );
        }

        return sprintf(
            '%s, fascia 07:00 - 19:00',
            $viewWindowStart->copy()->timezone('Europe/Rome')->format('d/m/Y'),
        );
    }
}
