<?php

namespace App\Livewire;

use App\Domain\OperatorActivity\OperatorActivityBuilder;
use App\Domain\OperatorActivity\OperatorActivityChartFormatter;
use App\Domain\ScoreReport\DailyScoreEvaluator;
use App\Models\Company;
use App\Models\ProductionTarget;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class OperatorStats extends Component
{
    private const TIMEZONE = 'Europe/Rome';
    private const METRIC_MODE_OPTIONS = [
        ['value' => 'amount', 'label' => 'Monetaria'],
        ['value' => 'score', 'label' => 'Punteggio'],
    ];
    private const WORK_STATUSES = [
        'to_do_count' => 'Da Lavorare',
        'in_progress_count' => 'In Lavorazione',
        'suspended_count' => 'Sospeso',
        'delivered_count' => 'Consegnato',
        'completed_count' => 'Fine Lavori',
        'ko_count' => 'KO',
    ];
    private const AVERAGE_PROCESSING_STATUSES = ['Consegnato', 'Fine Lavori'];
    private const TIMELINE_MODE_OPTIONS = [
        ['value' => 'day', 'label' => 'Giornaliera'],
        ['value' => 'week', 'label' => 'Settimanale'],
    ];
    private const STATUS_OPTIONS = [
        'Da Lavorare',
        'In Lavorazione',
        'Sospeso',
        'Attesa Fine Lavori',
        'KO',
        'Consegnato',
        'Fine Lavori',
    ];
    private const NTW_SCOPE_OPTIONS = [
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

    public $startDate;

    public $endDate;

    public $operatorId = '';

    public $status = '';

    public $companyId = '';

    public $workPhaseId = '';

    public $ntwScope = '';

    public $viewMode = 'day';

    public $metricMode = 'amount';

    public $selectedDay = '';

    public $selectedWeekStart = '';

    public ?int $lockedOperatorId = null;

    public bool $hideOperatorFilterWhenLocked = false;

    private ?float $amountTarget = null;

    private ?float $dailyScoreTarget = null;

    public function mount(?int $lockedOperatorId = null, bool $hideOperatorFilterWhenLocked = false)
    {
        $this->lockedOperatorId = $lockedOperatorId;
        $this->hideOperatorFilterWhenLocked = $hideOperatorFilterWhenLocked;
        $this->setDefaultDateFilters(Carbon::now(self::TIMEZONE));

        if ($this->lockedOperatorId !== null) {
            $this->operatorId = (string) $this->lockedOperatorId;
        }
    }

    public function render()
    {
        $t0 = microtime(true);

        $this->loadProductionTargets();

        [$startDate, $endDate] = $this->resolveReportRange($this->startDate, $this->endDate);
        [$viewWindowStart, $viewWindowEnd, $dayOptions, $weekOptions] = $this->resolveTimelineWindow($startDate, $endDate);
        $canViewEconomicReport = auth()->check() && auth()->user()->hasRole('admin');
        $operators = $this->resolveOperators($this->selectedOperatorId());

        Log::debug('render: setup ' . round((microtime(true) - $t0) * 1000) . 'ms');
        $t0 = microtime(true);

        $rows = $this->buildAllRows($operators, $startDate, $endDate);

        Log::debug('render: buildAllRows ' . round((microtime(true) - $t0) * 1000) . 'ms, operators=' . count($rows));
        $t0 = microtime(true);

        $timelineData = $this->formatTimelineData($rows, $viewWindowStart, $viewWindowEnd);

        Log::debug('render: formatTimelineData ' . round((microtime(true) - $t0) * 1000) . 'ms, series=' . count($timelineData));
        $t0 = microtime(true);

        $timelineConfig = $this->buildTimelineConfig($viewWindowStart, $viewWindowEnd);

        $this->dispatch('timeline-data', series: $timelineData, config: $timelineConfig);

        $view = view('livewire.operator-stats', $this->buildViewData(
            $rows,
            $timelineData,
            $timelineConfig,
            $dayOptions,
            $weekOptions,
            $viewWindowStart,
            $viewWindowEnd,
            $canViewEconomicReport,
        ));

        Log::debug('render: view() ' . round((microtime(true) - $t0) * 1000) . 'ms');

        return $view;
    }

    private function loadProductionTargets(): void
    {
        $target = ProductionTarget::current();
        $this->amountTarget = (float) $target->monthly_amount_target;
        $this->dailyScoreTarget = (float) $target->daily_score_target;
    }

    public function resetFilters(): void
    {
        $this->operatorId = $this->lockedOperatorId !== null ? (string) $this->lockedOperatorId : '';
        $this->status = '';
        $this->companyId = '';
        $this->workPhaseId = '';
        $this->ntwScope = '';
        $this->viewMode = 'day';
        $this->metricMode = 'amount';
        $this->setDefaultDateFilters(Carbon::now(self::TIMEZONE));
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

    private function resolveReportRange(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate, self::TIMEZONE)->startOfDay()->setTimezone('UTC'),
            Carbon::parse($endDate, self::TIMEZONE)->endOfDay()->setTimezone('UTC'),
        ];
    }

    private function buildAllRows(Collection $operators, Carbon $startDate, Carbon $endDate): array
    {
        if ($operators->isEmpty()) {
            return [];
        }

        $t0 = microtime(true);
        $operatorData = $this->loadOperatorData($operators, $startDate, $endDate);
        Log::debug('queries: ' . round((microtime(true) - $t0) * 1000) . 'ms');

        $t0 = microtime(true);
        $activityBuilder = app(OperatorActivityBuilder::class);
        $chartFormatter = app(OperatorActivityChartFormatter::class);
        $scoreEvaluator = app(DailyScoreEvaluator::class);
        $rows = [];

        foreach ($operators as $operator) {
            $rows[] = $this->buildOperatorRow(
                $operator,
                $operatorData['assignedWorks']->get($operator->id, collect()),
                $operatorData['earnedWorks']->get($operator->id, collect()),
                $operatorData['timesheets']->get($operator->id, collect()),
                $activityBuilder,
                $chartFormatter,
                $scoreEvaluator,
                $startDate,
                $endDate,
            );
        }

        Log::debug('computation: ' . round((microtime(true) - $t0) * 1000) . 'ms');

        return array_map(fn (array $row): array => $this->attachTimelineResolver($row), $rows);
    }

    private function loadOperatorData(Collection $operators, Carbon $startDate, Carbon $endDate): array
    {
        $operatorIds = $operators->pluck('id')->all();
        $startDateLocal = $startDate->copy()->timezone(self::TIMEZONE)->toDateString();
        $endDateLocal = $endDate->copy()->timezone(self::TIMEZONE)->toDateString();

        return [
            'assignedWorks' => $this->assignedWorksQuery($operatorIds, $startDate, $endDate)
                ->get()
                ->groupBy('_operator_id'),
            'earnedWorks' => $this->earnedWorksQuery($operatorIds, $startDateLocal, $endDateLocal)
                ->get()
                ->groupBy('_operator_id'),
            'timesheets' => Timesheet::query()
                ->whereIn('user_id', $operatorIds)
                ->whereBetween('date', [$startDateLocal, $endDateLocal])
                ->get()
                ->groupBy('user_id'),
        ];
    }

    private function assignedWorksQuery(array $operatorIds, Carbon $startDate, Carbon $endDate): Builder
    {
        return Work::query()
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
            ->with(['workSuspensions', 'workPhase', 'statusHistory']);
    }

    private function earnedWorksQuery(array $operatorIds, string $startDateLocal, string $endDateLocal): Builder
    {
        return Work::query()
            ->join('user_work', 'works.id', '=', 'user_work.work_id')
            ->whereIn('user_work.user_id', $operatorIds)
            ->whereBetween('works.completion_date', [$startDateLocal, $endDateLocal])
            ->tap(fn (Builder $query) => $this->applyWorkFilters($query))
            ->select('works.*', 'user_work.user_id as _operator_id')
            ->with('workPhase');
    }

    private function buildOperatorRow(
        User $operator,
        Collection $assignedWorks,
        Collection $earnedWorks,
        Collection $timesheets,
        OperatorActivityBuilder $activityBuilder,
        OperatorActivityChartFormatter $chartFormatter,
        DailyScoreEvaluator $scoreEvaluator,
        Carbon $startDate,
        Carbon $endDate,
    ): array {
        $activity = $activityBuilder->build($operator, $assignedWorks, $timesheets, $startDate, $endDate);
        $earnedMetrics = $this->earnedMetrics($earnedWorks);
        $scoreReport = $scoreEvaluator->evaluate($earnedWorks, $timesheets, $this->dailyScoreTarget ?? 0.0);

        $amountTarget = $this->amountTarget ?? 0.0;
        $targetPercentage = $amountTarget > 0
            ? round(($earnedMetrics['earned_amount'] / $amountTarget) * 100, 1)
            : 0.0;
        $scorePercentage = $scoreReport->achievementPercentage();

        return [
            'operator_id' => $operator->id,
            'operator_name' => $operator->name,
            'assigned_count' => $assignedWorks->count(),
            ...$this->statusCounts($assignedWorks),
            'average_processing_label' => $this->averageProcessingLabel($assignedWorks),
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
            'daily_breakdown' => $this->mergeScoreIntoBreakdown($activity->dailyBreakdown(), $scoreReport->daysByDate()),
            'weekly_summary' => $activity->aggregateBy('week'),
            'monthly_summary' => $activity->aggregateBy('month'),
            'nroe_total' => (int) $earnedWorks->sum(fn (Work $work) => (int) ($work->nroe ?? 0)),
            ...$earnedMetrics,
            'target_amount' => $amountTarget,
            'target_percentage' => $targetPercentage,
            'target_bar_width' => min(100, $targetPercentage),
            'target_class' => $this->targetClass($targetPercentage),
            'daily_score_target' => $this->dailyScoreTarget ?? 0.0,
            'score_target' => $scoreReport->targetTotal(),
            'score_percentage' => $scorePercentage,
            'score_bar_width' => min(100, $scorePercentage),
            'score_class' => $this->targetClass($scorePercentage),
            'score_expected_days' => $scoreReport->expectedDays(),
            'score_days_met' => $scoreReport->daysMet(),
            'score_days_below' => $scoreReport->daysBelow(),
            '_series' => $chartFormatter->forOperator($operator, $activity, $startDate, $endDate),
        ];
    }

    /**
     * Enrich the activity daily breakdown with per-day score evaluation.
     *
     * @param  array<int, array<string, mixed>>  $breakdown
     * @param  array<string, array<string, mixed>>  $scoreByDate
     * @return array<int, array<string, mixed>>
     */
    private function mergeScoreIntoBreakdown(array $breakdown, array $scoreByDate): array
    {
        return array_map(function (array $day) use ($scoreByDate): array {
            $score = $scoreByDate[$day['date']] ?? null;

            return $day + [
                'score_present' => $score !== null,
                'score_earned' => $score['earned_score'] ?? null,
                'score_target' => $score['target_score'] ?? null,
                'score_met' => $score['met'] ?? null,
            ];
        }, $breakdown);
    }

    private function statusCounts(Collection $assignedWorks): array
    {
        $counts = [];

        foreach (self::WORK_STATUSES as $key => $status) {
            $counts[$key] = $assignedWorks->where('status', $status)->count();
        }

        return $counts;
    }

    private function averageProcessingLabel(Collection $assignedWorks): string
    {
        $averageProcessingSeconds = $assignedWorks
            ->whereIn('status', self::AVERAGE_PROCESSING_STATUSES)
            ->filter(fn (Work $work) => $work->effective_processing_seconds !== null)
            ->map(fn (Work $work) => $work->effective_processing_seconds)
            ->avg();

        return $averageProcessingSeconds === null
            ? '-'
            : Work::formatDuration((int) round($averageProcessingSeconds));
    }

    private function earnedMetrics(Collection $earnedWorks): array
    {
        $valuedWorks = $earnedWorks->filter(fn (Work $work) => $work->accounting_amount !== null);
        $missingAmountWorks = $earnedWorks->filter(fn (Work $work) => $work->accounting_amount === null);

        return [
            'earned_amount' => round((float) $valuedWorks->sum(fn (Work $work) => (float) $work->accounting_amount), 2),
            'earned_works_count' => $valuedWorks->count(),
            'missing_amount_count' => $missingAmountWorks->count(),
            'earned_score' => round((float) $earnedWorks->sum(
                fn (Work $work) => (float) ($work->workPhase->score_coefficient ?? 0)
            ), 2),
        ];
    }

    private function attachTimelineResolver(array $row): array
    {
        $series = $row['_series'];
        unset($row['_series']);

        $row['timeline'] = fn (Carbon $windowStart, Carbon $windowEnd): array => $this->clipSeriesToWindow(
            $series,
            $windowStart,
            $windowEnd,
        );

        return $row;
    }

    private function clipSeriesToWindow(array $series, Carbon $windowStart, Carbon $windowEnd): array
    {
        $windowStartMs = $windowStart->getTimestamp() * 1000;
        $windowEndMs = $windowEnd->getTimestamp() * 1000;
        $result = [];

        foreach ($series as $entry) {
            if (($entry['name'] ?? '') === 'Sospensione') {
                continue;
            }

            $clippedData = [];

            foreach ($entry['data'] as $point) {
                [$startMs, $endMs] = $point['y'];

                if ($startMs >= $windowEndMs || $endMs <= $windowStartMs) {
                    continue;
                }

                $clippedStart = max($startMs, $windowStartMs);
                $clippedEnd = min($endMs, $windowEndMs);
                $point['y'] = [$clippedStart, $clippedEnd];
                $point['meta']['duration_label'] = Work::formatDuration((int) (($clippedEnd - $clippedStart) / 1000));
                $clippedData[] = $point;
            }

            if (! empty($clippedData)) {
                $result[] = [
                    'name' => $entry['name'],
                    'color' => $entry['color'],
                    'data' => $clippedData,
                ];
            }
        }

        return $result;
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
        $totalTarget = count($rows) * ($this->amountTarget ?? 0.0);
        $totalScore = round(array_sum(array_column($rows, 'earned_score')), 2);
        $totalScoreTarget = round((float) array_sum(array_column($rows, 'score_target')), 2);

        return [
            'total_earned' => $totalEarned,
            'total_target' => $totalTarget,
            'target_percentage' => $totalTarget > 0 ? round(($totalEarned / $totalTarget) * 100, 1) : 0.0,
            'total_score' => $totalScore,
            'total_score_target' => $totalScoreTarget,
            'score_percentage' => $totalScoreTarget > 0 ? round(($totalScore / $totalScoreTarget) * 100, 1) : 0.0,
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
        return self::STATUS_OPTIONS;
    }

    private function ntwScopeOptions(): array
    {
        return self::NTW_SCOPE_OPTIONS;
    }

    private function resolveTimelineWindow(Carbon $startDate, Carbon $endDate): array
    {
        $dayOptions = [];
        $weekOptions = [];
        $rangeStart = $startDate->copy()->timezone(self::TIMEZONE)->startOfDay();
        $rangeEnd = $endDate->copy()->timezone(self::TIMEZONE)->endOfDay();
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
            $weekStart = Carbon::parse($this->selectedWeekStart, self::TIMEZONE)
                ->startOfWeek()
                ->startOfDay()
                ->setTimezone('UTC');
            $weekEnd = Carbon::parse($this->selectedWeekStart, self::TIMEZONE)
                ->endOfWeek()
                ->endOfDay()
                ->setTimezone('UTC');

            return [$weekStart, $weekEnd, $dayOptions, $weekOptions];
        }

        $day = Carbon::parse($this->selectedDay, self::TIMEZONE);

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
                $viewWindowStart->copy()->timezone(self::TIMEZONE)->format('d/m/Y'),
                $viewWindowEnd->copy()->timezone(self::TIMEZONE)->format('d/m/Y'),
            );
        }

        return sprintf(
            '%s, fascia 07:00 - 19:00',
            $viewWindowStart->copy()->timezone(self::TIMEZONE)->format('d/m/Y'),
        );
    }

    private function setDefaultDateFilters(Carbon $now): void
    {
        $this->startDate = $now->copy()->startOfMonth()->toDateString();
        $this->endDate = $now->toDateString();
        $this->selectedDay = $now->toDateString();
        $this->selectedWeekStart = $now->copy()->startOfWeek()->toDateString();
    }

    private function selectedOperatorId(): string
    {
        return $this->lockedOperatorId !== null
            ? (string) $this->lockedOperatorId
            : $this->operatorId;
    }

    private function resolveOperators(string $selectedOperatorId): Collection
    {
        return User::permission('get works')
            ->when($selectedOperatorId !== '', fn (Builder $query) => $query->whereKey($selectedOperatorId))
            ->orderBy('name')
            ->get();
    }

    private function buildTimelineConfig(Carbon $viewWindowStart, Carbon $viewWindowEnd): array
    {
        return [
            'mode' => $this->viewMode,
            'min' => $viewWindowStart->getTimestamp() * 1000,
            'max' => $viewWindowEnd->getTimestamp() * 1000,
        ];
    }

    private function buildViewData(
        array $rows,
        array $timelineData,
        array $timelineConfig,
        array $dayOptions,
        array $weekOptions,
        Carbon $viewWindowStart,
        Carbon $viewWindowEnd,
        bool $canViewEconomicReport,
    ): array {
        return [
            'rows' => $rows,
            'economicSummary' => $this->buildEconomicSummary($rows),
            'canViewEconomicReport' => $canViewEconomicReport,
            'monthlyTarget' => $this->amountTarget ?? 0.0,
            'dailyScoreTarget' => $this->dailyScoreTarget ?? 0.0,
            'metricMode' => $this->metricMode,
            'metricModeOptions' => self::METRIC_MODE_OPTIONS,
            'operatorOptions' => User::permission('get works')->orderBy('name')->get(['id', 'name']),
            'hideOperatorFilter' => $this->hideOperatorFilterWhenLocked && $this->lockedOperatorId !== null,
            'companyOptions' => Company::orderBy('name')->get(['id', 'name']),
            'workPhaseOptions' => WorkPhase::orderBy('name')->get(['id', 'name']),
            'ntwScopeOptions' => $this->ntwScopeOptions(),
            'statusOptions' => $this->statusOptions(),
            'timelineData' => $timelineData,
            'dayOptions' => $dayOptions,
            'weekOptions' => $weekOptions,
            'timelineModeOptions' => self::TIMELINE_MODE_OPTIONS,
            'timelineWindowLabel' => $this->timelineWindowLabel($viewWindowStart, $viewWindowEnd),
            'timelineConfig' => $timelineConfig,
        ];
    }
}
