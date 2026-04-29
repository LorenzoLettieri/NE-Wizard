<?php

namespace App\Domain\OperatorActivity;

use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkSuspension;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class OperatorActivityBuilder
{
    public function build(
        User $operator,
        Collection $works,
        Collection $timesheets,
        Carbon $windowStart,
        Carbon $windowEnd,
    ): OperatorActivitySummary {
        $presence = $this->presenceIntervals($timesheets, $windowStart, $windowEnd);
        $breaks = $this->breakIntervals($timesheets, $windowStart, $windowEnd);
        $activeWork = $this->activeWorkIntervals($works, $windowStart, $windowEnd)
            ->intersect($presence)
            ->subtract($breaks)
            ->mergeOverlaps();

        return OperatorActivitySummary::fromCollections(
            presence: $presence,
            breaks: $breaks,
            activeWork: $activeWork,
            suspensions: $this->suspensionIntervals($works, $windowStart, $windowEnd),
            overtime: $this->overtimeIntervals($timesheets, $windowStart, $windowEnd),
            leaves: $this->leaveIntervals($timesheets, $windowStart, $windowEnd),
            rawWork: $this->rawWorkIntervals($works, $windowStart, $windowEnd),
            events: $this->eventIntervals($timesheets, $windowStart, $windowEnd),
        );
    }

    private function presenceIntervals(Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($timesheets as $timesheet) {
            $entryTime = $timesheet->effectiveShiftEntryTime();

            if (! $entryTime) {
                continue;
            }

            if (! $timesheet->exit_time && ! $this->isTodayTimesheet($timesheet)) {
                continue;
            }

            $end = $timesheet->exit_time
                ? $timesheet->exit_time->copy()
                : now()->min($windowEnd);

            $interval = (new OperatorActivityInterval(
                'presence',
                'Turno',
                $entryTime->copy()->max($windowStart),
                $end->min($windowEnd),
                ['timesheet_date' => $timesheet->date?->toDateString()],
            ))->withBounds($entryTime->copy()->max($windowStart), $end->min($windowEnd));

            if ($interval) {
                $intervals[] = $interval;
            }
        }

        return new OperatorActivityCollection($intervals);
    }

    private function isTodayTimesheet(Timesheet $timesheet): bool
    {
        return $timesheet->date?->toDateString() === now('Europe/Rome')->toDateString();
    }

    private function breakIntervals(Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($timesheets as $timesheet) {
            if (! $timesheet->hasClosedBreak()) {
                continue;
            }

            $intervals[] = new OperatorActivityInterval(
                'break',
                'Pausa',
                $timesheet->break_start->copy()->max($windowStart),
                $timesheet->break_end->copy()->min($windowEnd),
                ['timesheet_date' => $timesheet->date?->toDateString()],
            );
        }

        return new OperatorActivityCollection($intervals);
    }

    private function rawWorkIntervals(Collection $works, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($works as $work) {
            if (! $work->acception_date) {
                continue;
            }

            $start = $work->acception_date->copy()->max($windowStart);
            $end = ($work->delivery_date ? $work->delivery_date->copy() : now())->min($windowEnd);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $workLabel = $this->workTimelineLabel($work);
            $intervals[] = new OperatorActivityInterval(
                'raw_work',
                $workLabel,
                $start,
                $end,
                [
                    'work_id' => $work->id,
                    'work_label' => $workLabel,
                    'status' => $work->status,
                ],
            );
        }

        return new OperatorActivityCollection($intervals);
    }

    private function workTimelineLabel(Work $work): string
    {
        $phase = $work->workPhase?->name ?: $work->phase;

        return sprintf(
            'Lavoro: %s - %s - %s',
            $work->id ?? '-',
            $work->ntw_scope ?: '-',
            $phase ?: '-',
        );
    }

    private function suspensionIntervals(Collection $works, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($works as $work) {
            foreach ($work->workSuspensions as $suspension) {
                $start = $suspension->started_at->copy()->max($windowStart);
                $end = ($suspension->ended_at ? $suspension->ended_at->copy() : now())->min($windowEnd);

                if ($end->lessThanOrEqualTo($start)) {
                    continue;
                }

                $intervals[] = new OperatorActivityInterval(
                    'suspension',
                    'Sospensione: '.($work->wo_number ?? $work->id),
                    $start,
                    $end,
                    [
                        'work_id' => $work->id,
                        'work_label' => $work->wo_number ?? (string) $work->id,
                    ],
                );
            }
        }

        return new OperatorActivityCollection($intervals);
    }

    private function activeWorkIntervals(Collection $works, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($works as $work) {
            if (! $work->acception_date || $work->status === 'KO') {
                continue;
            }

            $workLabel = $this->workTimelineLabel($work);
            $meta = ['work_id' => $work->id, 'work_label' => $workLabel, 'status' => $work->status];

            $inLavorazioneHistory = $work->statusHistory->where('status', 'In Lavorazione')->values();

            if ($inLavorazioneHistory->isNotEmpty()) {
                // Usa la storia precisa registrata dall'observer
                $baseFragments = [];
                foreach ($inLavorazioneHistory as $h) {
                    $start = $h->started_at->copy()->max($windowStart);
                    $end = ($h->ended_at ?? now())->copy()->min($windowEnd);
                    if ($end->lte($start)) {
                        continue;
                    }
                    $baseFragments[] = new OperatorActivityInterval('active_work', $workLabel, $start, $end, $meta);
                }
            } else {
                // Fallback: usa acception_date → delivery_date sottraendo le sospensioni
                $start = $work->acception_date->copy()->max($windowStart);
                $end = ($work->delivery_date ?? now())->copy()->min($windowEnd);
                if ($end->lte($start)) {
                    continue;
                }
                $baseFragments = [new OperatorActivityInterval('active_work', $workLabel, $start, $end, $meta)];
            }

            // Sottrae le sospensioni (no-op se la history è già precisa, corretto per il backfill)
            $fragments = $baseFragments;
            foreach ($work->workSuspensions as $suspension) {
                $fragmentCollection = (new OperatorActivityCollection($fragments))->subtract(
                    new OperatorActivityCollection([
                        $this->toSuspensionInterval($work, $suspension, $windowStart, $windowEnd),
                    ]),
                );
                $fragments = array_values(array_filter(
                    $fragmentCollection->all(),
                    fn (?OperatorActivityInterval $fragment) => $fragment !== null,
                ));
            }

            array_push($intervals, ...$fragments);
        }

        return new OperatorActivityCollection($intervals);
    }

    private function overtimeIntervals(Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($timesheets as $timesheet) {
            if ((float) $timesheet->overtime_hours <= 0) {
                continue;
            }

            $baseStart = $timesheet->exit_time
                ? $timesheet->exit_time->copy()
                : Carbon::parse($timesheet->date->toDateString().' 18:00', 'Europe/Rome')->setTimezone('UTC');
            $seconds = (int) round(((float) $timesheet->overtime_hours) * 3600);
            $start = $baseStart->copy()->max($windowStart);
            $end = $baseStart->copy()->addSeconds($seconds)->min($windowEnd);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $intervals[] = new OperatorActivityInterval(
                'overtime',
                'Straordinario',
                $start,
                $end,
                ['timesheet_date' => $timesheet->date?->toDateString()],
            );
        }

        return new OperatorActivityCollection($intervals);
    }

    private function leaveIntervals(Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($timesheets as $timesheet) {
            if (! $timesheet->leave_type || (float) $timesheet->leave_hours <= 0) {
                continue;
            }

            if ($timesheet->leave_type === 'ferie') {
                $start = Carbon::parse($timesheet->date->toDateString().' 00:00:00', 'Europe/Rome')->setTimezone('UTC');
                $end = Carbon::parse($timesheet->date->toDateString().' 23:59:59', 'Europe/Rome')->setTimezone('UTC');
            } else {
                $leaveStartTime = $timesheet->effectiveLeaveStartTime();
                $start = $leaveStartTime
                    ? $leaveStartTime->copy()
                    : Carbon::parse($timesheet->date->toDateString().' 08:00:00', 'Europe/Rome')->setTimezone('UTC');
                $end = $start->copy()->addSeconds((int) round(((float) $timesheet->leave_hours) * 3600));
            }

            $start = $start->max($windowStart);
            $end = $end->min($windowEnd);

            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }

            $intervals[] = new OperatorActivityInterval(
                'leave',
                ucfirst((string) $timesheet->leave_type),
                $start,
                $end,
                [
                    'timesheet_date' => $timesheet->date?->toDateString(),
                    'leave_type' => $timesheet->leave_type,
                ],
            );
        }

        return new OperatorActivityCollection($intervals);
    }

    private function eventIntervals(Collection $timesheets, Carbon $windowStart, Carbon $windowEnd): OperatorActivityCollection
    {
        $intervals = [];

        foreach ($timesheets as $timesheet) {
            $events = [
                ['type' => 'shift_start_marker', 'label' => 'Ingresso turno', 'timestamp' => $timesheet->effectiveShiftEntryTime()],
                ['type' => 'break_start_marker', 'label' => 'Inizio pausa', 'timestamp' => $timesheet->break_start],
                ['type' => 'break_end_marker', 'label' => 'Fine pausa', 'timestamp' => $timesheet->break_end],
                ['type' => 'shift_end_marker', 'label' => 'Uscita turno', 'timestamp' => $timesheet->exit_time],
            ];

            foreach ($events as $event) {
                if (! $event['timestamp'] instanceof Carbon) {
                    continue;
                }

                $start = $event['timestamp']->copy();
                $end = $start->copy()->addMinutes(5);
                $interval = (new OperatorActivityInterval(
                    $event['type'],
                    $event['label'],
                    $start,
                    $end,
                    [
                        'event_label' => $event['label'],
                        'timesheet_date' => $timesheet->date?->toDateString(),
                    ],
                ))->withBounds($start->max($windowStart), $end->min($windowEnd));

                if ($interval) {
                    $intervals[] = $interval;
                }
            }
        }

        return new OperatorActivityCollection($intervals);
    }

    private function toSuspensionInterval(Work $work, WorkSuspension $suspension, Carbon $windowStart, Carbon $windowEnd): OperatorActivityInterval
    {
        $start = $suspension->started_at->copy()->max($windowStart);
        $end = ($suspension->ended_at ? $suspension->ended_at->copy() : now())->min($windowEnd);

        return new OperatorActivityInterval(
            'suspension',
            'Sospensione: '.($work->wo_number ?? $work->id),
            $start,
            $end,
            [
                'work_id' => $work->id,
                'work_label' => $work->wo_number ?? (string) $work->id,
            ],
        );
    }
}
