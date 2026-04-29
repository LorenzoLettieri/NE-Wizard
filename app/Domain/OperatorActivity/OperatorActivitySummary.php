<?php

namespace App\Domain\OperatorActivity;

use App\Models\Work;
use Carbon\Carbon;

final class OperatorActivitySummary
{
    private ?array $cachedDailyBreakdown = null;

    public function __construct(
        public readonly OperatorActivityCollection $presence,
        public readonly OperatorActivityCollection $breaks,
        public readonly OperatorActivityCollection $activeWork,
        public readonly OperatorActivityCollection $suspensions,
        public readonly OperatorActivityCollection $overtime,
        public readonly OperatorActivityCollection $leaves,
        public readonly OperatorActivityCollection $rawWork,
        public readonly OperatorActivityCollection $events,
    ) {
    }

    public static function fromCollections(
        OperatorActivityCollection $presence,
        OperatorActivityCollection $breaks,
        OperatorActivityCollection $activeWork,
        OperatorActivityCollection $suspensions,
        OperatorActivityCollection $overtime,
        OperatorActivityCollection $leaves,
        OperatorActivityCollection $rawWork,
        OperatorActivityCollection $events,
    ): self {
        return new self(
            $presence->mergeOverlaps(),
            $breaks->mergeOverlaps(),
            $activeWork->mergeOverlaps(),
            $suspensions->mergeOverlaps(),
            $overtime->mergeOverlaps(),
            $leaves->mergeOverlaps(),
            $rawWork->mergeOverlaps(),
            $events->mergeOverlaps(),
        );
    }

    public function presenceSeconds(): int
    {
        return $this->presence->totalSeconds();
    }

    public function breakSeconds(): int
    {
        return $this->breaks->totalSeconds();
    }

    public function activeWorkSeconds(): int
    {
        return $this->activeWork->totalSeconds();
    }

    public function suspensionSeconds(): int
    {
        return $this->suspensions->totalSeconds();
    }

    public function overtimeSeconds(): int
    {
        return $this->overtime->totalSeconds();
    }

    public function leaveSeconds(): int
    {
        return $this->leaves->totalSeconds();
    }

    public function utilizationPercentage(): float
    {
        if ($this->presenceSeconds() <= 0) {
            return 0.0;
        }

        return round(($this->activeWorkSeconds() / $this->presenceSeconds()) * 100, 1);
    }

    public function intervalsForChart(): array
    {
        return [
            'presence' => $this->presence->all(),
            'active_work' => $this->activeWork->all(),
            'break' => $this->breaks->all(),
            'leave' => $this->leaves->all(),
            'overtime' => $this->overtime->all(),
            'events' => $this->events->all(),
        ];
    }

    public function dailyBreakdown(string $timezone = 'Europe/Rome'): array
    {
        if ($this->cachedDailyBreakdown !== null) {
            return $this->cachedDailyBreakdown;
        }

        $dayMap = [];
        $collections = [
            'presence' => $this->presence,
            'active_work' => $this->activeWork,
            'break' => $this->breaks,
            'leave' => $this->leaves,
            'overtime' => $this->overtime,
            'suspension' => $this->suspensions,
        ];

        foreach ($collections as $key => $collection) {
            foreach ($collection->groupByDay($timezone) as $dateKey => $intervals) {
                $dayMap[$dateKey] ??= [
                    'date' => $dateKey,
                    'presence_seconds' => 0,
                    'active_work_seconds' => 0,
                    'break_seconds' => 0,
                    'leave_seconds' => 0,
                    'overtime_seconds' => 0,
                    'suspension_seconds' => 0,
                ];

                $seconds = (new OperatorActivityCollection($intervals))->totalSeconds();
                $dayMap[$dateKey]["{$key}_seconds"] += $seconds;
            }
        }

        ksort($dayMap);

        $result = array_map(function (array $day): array {
            $presenceSeconds = $day['presence_seconds'];
            $activeWorkSeconds = $day['active_work_seconds'];

            return [
                'date' => $day['date'],
                'date_label' => Carbon::parse($day['date'])->format('d/m/Y'),
                'presence_seconds' => $presenceSeconds,
                'presence_label' => Work::formatDuration($presenceSeconds),
                'active_work_seconds' => $activeWorkSeconds,
                'active_work_label' => Work::formatDuration($activeWorkSeconds),
                'break_seconds' => $day['break_seconds'],
                'break_label' => Work::formatDuration($day['break_seconds']),
                'leave_seconds' => $day['leave_seconds'],
                'leave_label' => Work::formatDuration($day['leave_seconds']),
                'overtime_seconds' => $day['overtime_seconds'],
                'overtime_label' => Work::formatDuration($day['overtime_seconds']),
                'suspension_seconds' => $day['suspension_seconds'],
                'suspension_label' => Work::formatDuration($day['suspension_seconds']),
                'utilization_percentage' => $presenceSeconds > 0
                    ? round(($activeWorkSeconds / $presenceSeconds) * 100, 1)
                    : 0.0,
            ];
        }, array_values($dayMap));

        return $this->cachedDailyBreakdown = $result;
    }

    public function aggregateBy(string $unit): array
    {
        $groups = [];

        foreach ($this->dailyBreakdown() as $day) {
            $key = match ($unit) {
                'month' => Carbon::createFromFormat('d/m/Y', $day['date_label'])->format('Y-m'),
                default => Carbon::createFromFormat('d/m/Y', $day['date_label'])->startOfWeek()->format('Y-m-d'),
            };

            $groups[$key] ??= [
                'label' => $key,
                'presence_seconds' => 0,
                'active_work_seconds' => 0,
                'break_seconds' => 0,
                'leave_seconds' => 0,
                'overtime_seconds' => 0,
                'suspension_seconds' => 0,
            ];

            $groups[$key]['presence_seconds'] += $day['presence_seconds'];
            $groups[$key]['active_work_seconds'] += $day['active_work_seconds'];
            $groups[$key]['break_seconds'] += $day['break_seconds'];
            $groups[$key]['leave_seconds'] += $day['leave_seconds'];
            $groups[$key]['overtime_seconds'] += $day['overtime_seconds'];
            $groups[$key]['suspension_seconds'] += $day['suspension_seconds'];
        }

        return array_values(array_map(function (array $group): array {
            return $group + [
                'presence_label' => Work::formatDuration($group['presence_seconds']),
                'active_work_label' => Work::formatDuration($group['active_work_seconds']),
                'break_label' => Work::formatDuration($group['break_seconds']),
                'leave_label' => Work::formatDuration($group['leave_seconds']),
                'overtime_label' => Work::formatDuration($group['overtime_seconds']),
                'suspension_label' => Work::formatDuration($group['suspension_seconds']),
                'utilization_percentage' => $group['presence_seconds'] > 0
                    ? round(($group['active_work_seconds'] / $group['presence_seconds']) * 100, 1)
                    : 0.0,
            ];
        }, $groups));
    }
}
