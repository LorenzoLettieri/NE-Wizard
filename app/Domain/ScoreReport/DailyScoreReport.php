<?php

namespace App\Domain\ScoreReport;

final class DailyScoreReport
{
    /**
     * @param  array<int, array{date:string, date_label:string, earned_score:float, target_score:float, worked_fraction:float, present:bool, met:bool}>  $days  Present-day rows only.
     * @param  float  $earnedScoreTotal  Score earned across the whole period (all completion days, present or not).
     */
    public function __construct(
        public readonly array $days,
        public readonly float $earnedScoreTotal,
    ) {
    }

    public function expectedDays(): int
    {
        return count($this->days);
    }

    public function daysMet(): int
    {
        return count(array_filter($this->days, fn (array $day): bool => $day['met']));
    }

    public function daysBelow(): int
    {
        return $this->expectedDays() - $this->daysMet();
    }

    public function targetTotal(): float
    {
        return round((float) array_sum(array_column($this->days, 'target_score')), 2);
    }

    public function earnedTotal(): float
    {
        return round($this->earnedScoreTotal, 2);
    }

    public function achievementPercentage(): float
    {
        $target = $this->targetTotal();

        return $target > 0 ? round(($this->earnedTotal() / $target) * 100, 1) : 0.0;
    }

    /**
     * Present-day rows keyed by date string, for merging into other daily breakdowns.
     *
     * @return array<string, array<string, mixed>>
     */
    public function daysByDate(): array
    {
        $map = [];

        foreach ($this->days as $day) {
            $map[$day['date']] = $day;
        }

        return $map;
    }
}
