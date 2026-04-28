# Operator Activity Report Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the current pseudo-Gantt with a precise operator activity reporting feature that combines work lifecycle data and timesheet events into reliable daily, weekly, and monthly activity reports.

**Architecture:** Introduce a dedicated activity domain layer that converts `Work`, `WorkSuspension`, and `Timesheet` records into a unified stream of operator activity intervals. Compute reporting metrics from that stream instead of mixing business logic inside Livewire components and Blade views. Keep `OperatorStats` as the presentation layer, move calculation logic into focused services, and make the chart consume a stable DTO-like structure.

**Tech Stack:** Laravel 12, Livewire, Eloquent, Carbon, PHPUnit, ApexCharts

---

## File Structure

- Modify: `app/Livewire/OperatorStats.php`
  Responsibility: reduce to filter handling, authorization, and view-model wiring.
- Modify: `resources/views/livewire/operator-stats.blade.php`
  Responsibility: render the new timeline, summaries, legends, and period breakdowns.
- Modify: `app/Livewire/OperatorTimesheet.php`
  Responsibility: enforce valid operator-side timesheet state transitions.
- Modify: `app/Livewire/AdminTimesheetDashboard.php`
  Responsibility: reuse shared timesheet calculations and validation rules.
- Modify: `app/Models/Timesheet.php`
  Responsibility: expose normalized helpers and casts used by reporting services.
- Modify: `database/migrations/2026_01_19_102729_create_timesheets_table.php`
  Responsibility: source reference only; do not edit. Use it to align constraints with real schema.
- Create: `app/Domain/OperatorActivity/OperatorActivityInterval.php`
  Responsibility: immutable value object describing one interval in the unified activity timeline.
- Create: `app/Domain/OperatorActivity/OperatorActivityCollection.php`
  Responsibility: interval collection helpers for merge, clip, subtract, and summarize operations.
- Create: `app/Domain/OperatorActivity/OperatorActivityBuilder.php`
  Responsibility: build a unified interval stream from works, suspensions, and timesheets.
- Create: `app/Domain/OperatorActivity/OperatorActivitySummary.php`
  Responsibility: period aggregates for daily, weekly, and monthly reports.
- Create: `app/Domain/OperatorActivity/OperatorActivityChartFormatter.php`
  Responsibility: convert normalized intervals into chart series for ApexCharts.
- Create: `app/Support/Timesheets/TimesheetStateMachine.php`
  Responsibility: validate allowed timesheet actions and protect backend flows.
- Create: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`
  Responsibility: verify interval composition logic.
- Create: `tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php`
  Responsibility: verify backend timesheet transition rules.
- Modify: `tests/Feature/OperatorStatsTest.php`
  Responsibility: update feature expectations to the new reporting semantics.
- Modify: `tests/Feature/WorkSuspensionFeatureTest.php`
  Responsibility: preserve suspension correctness inside the new reporting model.
- Create: `tests/Feature/OperatorTimesheetFlowTest.php`
  Responsibility: verify end-to-end timesheet action constraints and valid flows.

## Refactor Targets

1. Make the timeline represent **actual operator activity**, not only work lead time.
2. Merge these sources into one model:
   - work intervals: accepted work, active work, delivered work
   - work suspension intervals
   - shift entry/exit intervals
   - break intervals
   - leave intervals
   - overtime intervals
3. Produce consistent totals for:
   - active work time
   - shift presence time
   - break time
   - leave time by type
   - suspension time
   - overtime time
   - utilization against presence
4. Generate precise daily rows, plus weekly and monthly aggregates from the same engine.
5. Remove duplicated time arithmetic from Blade and Livewire.
6. Harden backend validation so the UI is not the only guardrail.

---

### Task 1: Freeze Current Behavior With Characterization Tests

**Files:**
- Modify: `tests/Feature/OperatorStatsTest.php`
- Create: `tests/Feature/OperatorTimesheetFlowTest.php`
- Test: `tests/Feature/OperatorStatsTest.php`
- Test: `tests/Feature/OperatorTimesheetFlowTest.php`

- [ ] **Step 1: Write failing feature tests for the missing business guarantees**

```php
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
                && $row['presence_label'] === '9h'
                && $row['break_label'] === '30m'
                && $row['active_work_label'] === '3h 30m'
                && $row['overtime_label'] === '1h';
        });
}
```

```php
public function test_end_break_is_rejected_when_no_break_is_open(): void
{
    $this->seed(RoleSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole('operator');

    Timesheet::create([
        'user_id' => $operator->id,
        'date' => now()->toDateString(),
        'entry_time' => now()->startOfDay()->addHours(8),
    ]);

    $this->actingAs($operator);

    Livewire::test(OperatorTimesheet::class)
        ->set('actionType', 'end_break')
        ->set('selectedDate', now()->toDateString())
        ->call('saveAction')
        ->assertHasErrors(['actionType']);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/OperatorStatsTest.php tests/Feature/OperatorTimesheetFlowTest.php`

Expected: FAIL because the current component does not expose normalized presence metrics and does not reject invalid timesheet transitions server-side.

- [ ] **Step 3: Commit the failing tests**

```bash
git add tests/Feature/OperatorStatsTest.php tests/Feature/OperatorTimesheetFlowTest.php
git commit -m "test: characterize operator activity reporting gaps"
```

### Task 2: Introduce Unified Activity Domain Objects

**Files:**
- Create: `app/Domain/OperatorActivity/OperatorActivityInterval.php`
- Create: `app/Domain/OperatorActivity/OperatorActivityCollection.php`
- Create: `app/Domain/OperatorActivity/OperatorActivitySummary.php`
- Create: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`
- Test: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

- [ ] **Step 1: Write failing unit tests for interval math**

```php
public function test_collection_merges_overlapping_intervals_of_same_type(): void
{
    $collection = new OperatorActivityCollection([
        new OperatorActivityInterval('active_work', 'WO-1', Carbon::parse('2026-04-10 08:00:00', 'UTC'), Carbon::parse('2026-04-10 10:00:00', 'UTC')),
        new OperatorActivityInterval('active_work', 'WO-1', Carbon::parse('2026-04-10 09:30:00', 'UTC'), Carbon::parse('2026-04-10 11:00:00', 'UTC')),
    ]);

    $merged = $collection->mergeOverlaps();

    $this->assertCount(1, $merged->all());
    $this->assertSame(10800, $merged->totalSecondsByType('active_work'));
}
```

```php
public function test_collection_subtracts_breaks_from_presence_without_removing_non_overlapping_time(): void
{
    $presence = new OperatorActivityCollection([
        new OperatorActivityInterval('presence', 'shift', Carbon::parse('2026-04-10 08:00:00', 'UTC'), Carbon::parse('2026-04-10 17:00:00', 'UTC')),
    ]);

    $breaks = new OperatorActivityCollection([
        new OperatorActivityInterval('break', 'lunch', Carbon::parse('2026-04-10 12:00:00', 'UTC'), Carbon::parse('2026-04-10 12:30:00', 'UTC')),
    ]);

    $net = $presence->subtract($breaks);

    $this->assertSame(30600, $net->totalSecondsByType('presence'));
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

Expected: FAIL because the new domain objects do not exist yet.

- [ ] **Step 3: Implement the minimal value objects**

```php
final class OperatorActivityInterval
{
    public function __construct(
        public readonly string $type,
        public readonly string $label,
        public readonly Carbon $start,
        public readonly Carbon $end,
        public readonly array $meta = [],
    ) {
    }

    public function seconds(): int
    {
        return max(0, $this->start->diffInSeconds($this->end, false));
    }
}
```

```php
final class OperatorActivityCollection
{
    /** @param array<int,OperatorActivityInterval> $intervals */
    public function __construct(private array $intervals = [])
    {
    }

    public function all(): array
    {
        return $this->intervals;
    }

    public function totalSecondsByType(string $type): int
    {
        return collect($this->intervals)
            ->where('type', $type)
            ->sum(fn (OperatorActivityInterval $interval) => $interval->seconds());
    }
}
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Domain/OperatorActivity/OperatorActivityInterval.php app/Domain/OperatorActivity/OperatorActivityCollection.php app/Domain/OperatorActivity/OperatorActivitySummary.php tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php
git commit -m "feat: add operator activity domain primitives"
```

### Task 3: Build the Unified Activity Builder

**Files:**
- Create: `app/Domain/OperatorActivity/OperatorActivityBuilder.php`
- Modify: `app/Models/Timesheet.php`
- Modify: `app/Models/Work.php`
- Modify: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`
- Test: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

- [ ] **Step 1: Write failing unit tests for combined intervals**

```php
public function test_builder_combines_work_suspensions_presence_breaks_and_overtime(): void
{
    $operator = User::factory()->make(['id' => 99]);

    $work = Work::make([
        'wo_number' => 'WO-100',
        'acception_date' => Carbon::parse('2026-04-10 08:30:00', 'UTC'),
        'delivery_date' => Carbon::parse('2026-04-10 12:30:00', 'UTC'),
    ]);

    $work->setRelation('workSuspensions', collect([
        WorkSuspension::make([
            'started_at' => Carbon::parse('2026-04-10 09:30:00', 'UTC'),
            'ended_at' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
        ]),
    ]));

    $timesheet = Timesheet::make([
        'date' => '2026-04-10',
        'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
        'break_start' => Carbon::parse('2026-04-10 11:00:00', 'UTC'),
        'break_end' => Carbon::parse('2026-04-10 11:30:00', 'UTC'),
        'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
        'overtime_hours' => 1,
    ]);

    $summary = app(OperatorActivityBuilder::class)->build(
        $operator,
        collect([$work]),
        collect([$timesheet]),
        Carbon::parse('2026-04-10 00:00:00', 'UTC'),
        Carbon::parse('2026-04-10 23:59:59', 'UTC'),
    );

    $this->assertSame(32400, $summary->presenceSeconds());
    $this->assertSame(1800, $summary->breakSeconds());
    $this->assertSame(12600, $summary->activeWorkSeconds());
    $this->assertSame(1800, $summary->suspensionSeconds());
    $this->assertSame(3600, $summary->overtimeSeconds());
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

Expected: FAIL because `OperatorActivityBuilder` and summary methods do not exist yet.

- [ ] **Step 3: Implement the builder with explicit interval types**

```php
final class OperatorActivityBuilder
{
    public function build(User $operator, Collection $works, Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivitySummary
    {
        $presence = $this->presenceIntervals($timesheets, $windowStart, $windowEnd);
        $breaks = $this->breakIntervals($timesheets, $windowStart, $windowEnd);
        $work = $this->workIntervals($works, $windowStart, $windowEnd);
        $suspensions = $this->suspensionIntervals($works, $windowStart, $windowEnd);
        $overtime = $this->overtimeIntervals($timesheets, $windowStart, $windowEnd);
        $leaves = $this->leaveIntervals($timesheets, $windowStart, $windowEnd);

        return OperatorActivitySummary::fromCollections(
            presence: $presence,
            breaks: $breaks,
            activeWork: $work->subtract($suspensions)->subtract($breaks),
            suspensions: $suspensions,
            overtime: $overtime,
            leaves: $leaves,
            rawWork: $work,
        );
    }
}
```

- [ ] **Step 4: Add model helpers only if they remove duplication**

```php
public function hasClosedBreak(): bool
{
    return $this->break_start !== null && $this->break_end !== null;
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Domain/OperatorActivity/OperatorActivityBuilder.php app/Models/Timesheet.php app/Models/Work.php tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php
git commit -m "feat: build unified operator activity timeline"
```

### Task 4: Harden Timesheet Backend Rules

**Files:**
- Create: `app/Support/Timesheets/TimesheetStateMachine.php`
- Modify: `app/Livewire/OperatorTimesheet.php`
- Modify: `app/Livewire/AdminTimesheetDashboard.php`
- Create: `tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php`
- Modify: `tests/Feature/OperatorTimesheetFlowTest.php`
- Test: `tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php`
- Test: `tests/Feature/OperatorTimesheetFlowTest.php`

- [ ] **Step 1: Write failing tests for transition validity**

```php
public function test_state_machine_rejects_shift_end_without_shift_start(): void
{
    $result = app(TimesheetStateMachine::class)->validate(
        action: 'end_shift',
        timesheet: new Timesheet(),
    );

    $this->assertFalse($result->allowed);
    $this->assertSame('shift_not_started', $result->reason);
}
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php tests/Feature/OperatorTimesheetFlowTest.php`

Expected: FAIL because the validator does not exist and Livewire still relies on button disabling.

- [ ] **Step 3: Implement backend transition validation**

```php
final class TimesheetStateMachine
{
    public function validate(string $action, ?Timesheet $timesheet): TimesheetTransitionResult
    {
        return match ($action) {
            'start_shift' => TimesheetTransitionResult::allowWhen(! $timesheet?->entry_time, 'shift_already_started'),
            'start_break' => TimesheetTransitionResult::allowWhen($timesheet?->entry_time && ! $timesheet?->break_start && ! $timesheet?->exit_time, 'break_not_allowed'),
            'end_break' => TimesheetTransitionResult::allowWhen($timesheet?->break_start && ! $timesheet?->break_end && ! $timesheet?->exit_time, 'break_not_open'),
            'end_shift' => TimesheetTransitionResult::allowWhen($timesheet?->entry_time && ! $timesheet?->exit_time, 'shift_not_started'),
            default => TimesheetTransitionResult::allowed(),
        };
    }
}
```

- [ ] **Step 4: Wire the validator into operator and admin flows**

```php
$transition = app(TimesheetStateMachine::class)->validate($this->actionType, $timesheet);

if (! $transition->allowed) {
    $this->addError('actionType', $transition->message());
    return;
}
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php tests/Feature/OperatorTimesheetFlowTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Support/Timesheets/TimesheetStateMachine.php app/Livewire/OperatorTimesheet.php app/Livewire/AdminTimesheetDashboard.php tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php tests/Feature/OperatorTimesheetFlowTest.php
git commit -m "fix: enforce backend timesheet state transitions"
```

### Task 5: Refactor OperatorStats to Use the New Reporting Engine

**Files:**
- Modify: `app/Livewire/OperatorStats.php`
- Create: `app/Domain/OperatorActivity/OperatorActivityChartFormatter.php`
- Modify: `tests/Feature/OperatorStatsTest.php`
- Test: `tests/Feature/OperatorStatsTest.php`

- [ ] **Step 1: Write failing feature assertions for new report fields**

```php
->assertViewHas('rows', function ($rows) {
    $row = collect($rows)->firstWhere('operator_name', 'Mario Rossi');

    return $row
        && array_key_exists('presence_label', $row)
        && array_key_exists('break_label', $row)
        && array_key_exists('active_work_label', $row)
        && array_key_exists('utilization_percentage', $row)
        && array_key_exists('daily_breakdown', $row);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: FAIL because `OperatorStats` still returns the old timeline-only metrics.

- [ ] **Step 3: Replace inline calculations with builder calls**

```php
$timesheets = Timesheet::query()
    ->where('user_id', $operator->id)
    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
    ->get();

$activity = app(OperatorActivityBuilder::class)->build(
    $operator,
    $assignedWorks,
    $timesheets,
    $startDate,
    $endDate,
);
```

```php
return [
    'operator_id' => $operator->id,
    'operator_name' => $operator->name,
    'assigned_count' => $assignedWorks->count(),
    'presence_label' => Work::formatDuration($activity->presenceSeconds()),
    'break_label' => Work::formatDuration($activity->breakSeconds()),
    'active_work_label' => Work::formatDuration($activity->activeWorkSeconds()),
    'suspension_label' => Work::formatDuration($activity->suspensionSeconds()),
    'overtime_label' => Work::formatDuration($activity->overtimeSeconds()),
    'leave_label' => Work::formatDuration($activity->leaveSeconds()),
    'utilization_percentage' => $activity->utilizationPercentage(),
    'daily_breakdown' => $activity->dailyBreakdown(),
    'timeline' => app(OperatorActivityChartFormatter::class)->forOperator($operator, $activity),
];
```

- [ ] **Step 4: Remove N+1 query patterns where possible**

```php
$operators = User::permission('get works')
    ->when($this->operatorId !== '', fn (Builder $query) => $query->whereKey($this->operatorId))
    ->with([
        'works' => fn ($query) => $query->with('workSuspensions'),
    ])
    ->orderBy('name')
    ->get();
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/OperatorStats.php app/Domain/OperatorActivity/OperatorActivityChartFormatter.php tests/Feature/OperatorStatsTest.php
git commit -m "refactor: move operator stats to unified activity engine"
```

### Task 6: Upgrade the Chart and Reporting UI

**Files:**
- Modify: `resources/views/livewire/operator-stats.blade.php`
- Test: `tests/Feature/OperatorStatsTest.php`

- [ ] **Step 1: Write failing feature assertions for the new UI labels**

```php
Livewire::test(OperatorStats::class)
    ->assertSee('Presenza')
    ->assertSee('Pausa')
    ->assertSee('Lavoro attivo')
    ->assertSee('Utilizzo')
    ->assertSee('Dettaglio giornaliero');
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: FAIL because the current Blade template does not render these sections.

- [ ] **Step 3: Render the chart as a real operator-activity timeline**

```js
const options = {
  chart: { type: 'rangeBar', height: 550, toolbar: { show: true } },
  plotOptions: { bar: { horizontal: true, rangeBarGroupRows: true, barHeight: '70%' } },
  xaxis: { type: 'datetime', labels: { datetimeUTC: false } },
  legend: { show: true, position: 'top' },
  tooltip: {
    custom: ({ seriesIndex, dataPointIndex, w }) => {
      const point = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
      return `<div class="p-2"><strong>${point.meta.type_label}</strong><br>${point.meta.label}<br>${point.meta.start_local} - ${point.meta.end_local}<br>${point.meta.duration_label}</div>`;
    }
  }
};
```

- [ ] **Step 4: Add daily summary rows under each operator**

```blade
@foreach ($row['daily_breakdown'] as $day)
    <tr>
        <td>{{ $day['date_label'] }}</td>
        <td class="text-end">{{ $day['presence_label'] }}</td>
        <td class="text-end">{{ $day['active_work_label'] }}</td>
        <td class="text-end">{{ $day['break_label'] }}</td>
        <td class="text-end">{{ $day['leave_label'] }}</td>
        <td class="text-end">{{ $day['overtime_label'] }}</td>
        <td class="text-end">{{ $day['utilization_percentage'] }}%</td>
    </tr>
@endforeach
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add resources/views/livewire/operator-stats.blade.php tests/Feature/OperatorStatsTest.php
git commit -m "feat: add precise operator activity timeline and daily breakdown"
```

### Task 7: Add Weekly and Monthly Aggregate Reports From the Same Engine

**Files:**
- Modify: `app/Livewire/OperatorStats.php`
- Modify: `resources/views/livewire/operator-stats.blade.php`
- Modify: `app/Livewire/AdminTimesheetDashboard.php`
- Modify: `tests/Feature/OperatorStatsTest.php`
- Test: `tests/Feature/OperatorStatsTest.php`

- [ ] **Step 1: Write failing feature tests for weekly/monthly aggregate fields**

```php
->assertViewHas('rows', function ($rows) {
    $row = collect($rows)->firstWhere('operator_name', 'Mario Rossi');

    return $row
        && array_key_exists('weekly_summary', $row)
        && array_key_exists('monthly_summary', $row);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: FAIL because aggregate summaries are not exposed yet.

- [ ] **Step 3: Build aggregates from normalized daily summaries**

```php
$weekly = $activity->aggregateBy('week');
$monthly = $activity->aggregateBy('month');
```

- [ ] **Step 4: Reuse shared totals in admin timesheet reporting**

```php
$monthlyReport[] = [
    'user' => $user,
    'presence_hours' => $summary->presenceLabel(),
    'active_work_hours' => $summary->activeWorkLabel(),
    'break_hours' => $summary->breakLabel(),
    'leave_hours' => $summary->leaveLabel(),
    'overtime_hours' => $summary->overtimeLabel(),
];
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test tests/Feature/OperatorStatsTest.php`

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/OperatorStats.php resources/views/livewire/operator-stats.blade.php app/Livewire/AdminTimesheetDashboard.php tests/Feature/OperatorStatsTest.php
git commit -m "feat: add weekly and monthly operator activity summaries"
```

### Task 8: Performance, Verification, and Cleanup

**Files:**
- Modify: `app/Livewire/OperatorStats.php`
- Modify: `resources/views/livewire/operator-stats.blade.php`
- Modify: `tests/Feature/WorkSuspensionFeatureTest.php`
- Test: `tests/Feature/OperatorStatsTest.php`
- Test: `tests/Feature/OperatorTimesheetFlowTest.php`
- Test: `tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php`
- Test: `tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php`

- [ ] **Step 1: Remove leftover duplicated duration math from Blade**

```blade
{{ $row['presence_label'] }}
{{ $row['active_work_label'] }}
{{ $row['break_label'] }}
```

- [ ] **Step 2: Ensure chart updates do not rely on brittle inline comparisons**

```js
window.dispatchEvent(new CustomEvent('operator-activity-series-updated', { detail: @json($timelineData) }));
```

- [ ] **Step 3: Run the focused reporting test suite**

Run: `php artisan test tests/Feature/OperatorStatsTest.php tests/Feature/OperatorTimesheetFlowTest.php tests/Feature/WorkSuspensionFeatureTest.php tests/Unit/Domain/OperatorActivity/OperatorActivityBuilderTest.php tests/Unit/Support/Timesheets/TimesheetStateMachineTest.php`

Expected: PASS

- [ ] **Step 4: Run the broader app suite if feasible**

Run: `php artisan test`

Expected: PASS, or document unrelated failures before merge.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/OperatorStats.php resources/views/livewire/operator-stats.blade.php tests/Feature/WorkSuspensionFeatureTest.php
git commit -m "refactor: finalize operator activity reporting stack"
```

---

## Implementation Notes

- Use `Europe/Rome` consistently for display formatting, but normalize interval storage and calculations in `UTC`.
- Keep current economic metrics, but derive them separately from activity time metrics.
- Do not treat overtime as implicit active work unless business rules explicitly require it.
- For leave records:
  - `ferie`: full-day leave intervals
  - `permesso`: partial-day leave intervals
  - `malattia`: partial-day or full-day leave intervals based on entered hours
- If timesheet data overlaps a work interval, keep both concepts:
  - `presence`: operator was on shift
  - `active_work`: operator was working on assigned work
  This is required to calculate utilization instead of collapsing everything into one number.

## Expected End State

- The chart shows a row per operator with multiple colored interval types:
  - presence
  - active work
  - suspension
  - break
  - leave
  - overtime
- Daily rows explain each operator’s real activity.
- Weekly and monthly summaries are derived from the same normalized engine.
- Backend rules guarantee that invalid timesheet transitions do not corrupt reporting.
- `OperatorStats` becomes a thin composition layer instead of the calculation engine.

## Self-Review

- Spec coverage: covered unified timeline, timesheet hardening, reporting breakdowns, weekly/monthly aggregates, performance, and verification.
- Placeholder scan: no `TODO`/`TBD` placeholders left in tasks.
- Type consistency: the plan consistently uses `OperatorActivityBuilder`, `OperatorActivityCollection`, `OperatorActivitySummary`, and `TimesheetStateMachine`.

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-04-28-operator-activity-report-refactor.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
