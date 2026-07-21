<?php

namespace App\Domain\ScoreReport;

use App\Models\Timesheet;
use App\Models\Work;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DailyScoreEvaluator
{
    /**
     * Standard working day, in hours. Matches the domain assumptions already used
     * elsewhere (ferie booked as 8h, overtime baseline at 18:00).
     */
    private const STANDARD_WORK_HOURS = 8.0;

    public function __construct(private readonly string $timezone = 'Europe/Rome')
    {
    }

    /**
     * Evaluate an operator's daily score against a daily threshold, counting only
     * days with recorded presence and pro-rating the threshold for partial (hourly)
     * leaves. Full-day leaves (ferie / whole-day sickness) are simply absent from the
     * present-day set and therefore excluded from the denominator.
     *
     * @param  Collection<int, Work>  $earnedWorks  Works completed in the period (with workPhase loaded).
     * @param  Collection<int, Timesheet>  $timesheets  Timesheets in the period.
     */
    public function evaluate(Collection $earnedWorks, Collection $timesheets, float $dailyScoreTarget): DailyScoreReport
    {
        $scoreByDay = $this->scoreByDay($earnedWorks);
        $days = [];

        foreach ($timesheets as $timesheet) {
            $date = $timesheet->date?->toDateString();

            if ($date === null || ! $timesheet->effectiveShiftEntryTime()) {
                continue;
            }

            $workedFraction = $this->workedFraction($timesheet);
            $target = round($dailyScoreTarget * $workedFraction, 2);
            $earned = round((float) ($scoreByDay[$date] ?? 0.0), 2);

            $days[$date] = [
                'date' => $date,
                'date_label' => Carbon::parse($date)->format('d/m/Y'),
                'earned_score' => $earned,
                'target_score' => $target,
                'worked_fraction' => $workedFraction,
                'present' => true,
                'met' => $earned >= $target,
            ];
        }

        ksort($days);

        return new DailyScoreReport(
            array_values($days),
            (float) array_sum($scoreByDay),
        );
    }

    /**
     * @param  Collection<int, Work>  $earnedWorks
     * @return array<string, float>
     */
    private function scoreByDay(Collection $earnedWorks): array
    {
        $map = [];

        foreach ($earnedWorks as $work) {
            $date = $work->completion_date?->toDateString();

            if ($date === null) {
                continue;
            }

            $map[$date] = ($map[$date] ?? 0.0) + (float) ($work->workPhase->score_coefficient ?? 0);
        }

        return $map;
    }

    private function workedFraction(Timesheet $timesheet): float
    {
        if (! $timesheet->isHourlyLeave()) {
            return 1.0;
        }

        $fraction = (self::STANDARD_WORK_HOURS - (float) $timesheet->leave_hours) / self::STANDARD_WORK_HOURS;

        return max(0.0, min(1.0, $fraction));
    }
}
