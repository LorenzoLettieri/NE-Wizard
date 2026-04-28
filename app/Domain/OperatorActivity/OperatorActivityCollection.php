<?php

namespace App\Domain\OperatorActivity;

use Carbon\Carbon;
use Illuminate\Support\Collection;

final class OperatorActivityCollection
{
    /** @var array<int,OperatorActivityInterval> */
    private array $intervals;

    /**
     * @param  array<int,OperatorActivityInterval>  $intervals
     */
    public function __construct(array $intervals = [])
    {
        $this->intervals = array_values($intervals);
    }

    /**
     * @return array<int,OperatorActivityInterval>
     */
    public function all(): array
    {
        return $this->intervals;
    }

    public function push(OperatorActivityInterval $interval): self
    {
        $copy = $this->intervals;
        $copy[] = $interval;

        return new self($copy);
    }

    public function mergeOverlaps(): self
    {
        $merged = [];

        /** @var Collection<string, Collection<int, OperatorActivityInterval>> $groups */
        $groups = collect($this->intervals)->groupBy(fn (OperatorActivityInterval $interval) => $this->mergeKey($interval));

        foreach ($groups as $typeIntervals) {
            $sorted = $typeIntervals
                ->sortBy(fn (OperatorActivityInterval $interval) => $interval->start->getTimestamp())
                ->values();

            $current = null;

            foreach ($sorted as $interval) {
                if ($current === null) {
                    $current = $interval;
                    continue;
                }

                if ($current->overlaps($interval) || $current->touches($interval)) {
                    $current = new OperatorActivityInterval(
                        $current->type,
                        $current->label,
                        $current->start,
                        $current->end->max($interval->end),
                        $current->meta + $interval->meta,
                    );

                    continue;
                }

                $merged[] = $current;
                $current = $interval;
            }

            if ($current !== null) {
                $merged[] = $current;
            }
        }

        usort($merged, fn (OperatorActivityInterval $a, OperatorActivityInterval $b) => $a->start->getTimestamp() <=> $b->start->getTimestamp());

        return new self($merged);
    }

    private function mergeKey(OperatorActivityInterval $interval): string
    {
        if (in_array($interval->type, ['active_work', 'raw_work', 'suspension'], true)) {
            return implode(':', [
                $interval->type,
                $interval->meta['work_id'] ?? $interval->label,
            ]);
        }

        if (str_ends_with($interval->type, '_marker')) {
            return implode(':', [
                $interval->type,
                $interval->start->getTimestamp(),
            ]);
        }

        return $interval->type;
    }

    public function subtract(self $other): self
    {
        $result = [];

        foreach ($this->intervals as $interval) {
            $fragments = [$interval];

            foreach ($other->all() as $subtractor) {
                $nextFragments = [];

                foreach ($fragments as $fragment) {
                    if (! $fragment->overlaps($subtractor)) {
                        $nextFragments[] = $fragment;
                        continue;
                    }

                    if ($subtractor->start->gt($fragment->start)) {
                        $left = $fragment->withBounds($fragment->start, $subtractor->start);
                        if ($left) {
                            $nextFragments[] = $left;
                        }
                    }

                    if ($subtractor->end->lt($fragment->end)) {
                        $right = $fragment->withBounds($subtractor->end, $fragment->end);
                        if ($right) {
                            $nextFragments[] = $right;
                        }
                    }
                }

                $fragments = $nextFragments;
            }

            array_push($result, ...$fragments);
        }

        return new self($result);
    }

    public function clip(Carbon $windowStart, Carbon $windowEnd): self
    {
        $clipped = [];

        foreach ($this->intervals as $interval) {
            $start = $interval->start->copy()->max($windowStart);
            $end = $interval->end->copy()->min($windowEnd);
            $bounded = $interval->withBounds($start, $end);

            if ($bounded) {
                $clipped[] = $bounded;
            }
        }

        return new self($clipped);
    }

    public function intersect(self $other): self
    {
        $result = [];

        foreach ($this->intervals as $interval) {
            foreach ($other->all() as $candidate) {
                $start = $interval->start->copy()->max($candidate->start);
                $end = $interval->end->copy()->min($candidate->end);
                $bounded = $interval->withBounds($start, $end);

                if ($bounded) {
                    $result[] = $bounded;
                }
            }
        }

        return new self($result);
    }

    public function totalSecondsByType(string $type): int
    {
        return (int) collect($this->intervals)
            ->where(fn (OperatorActivityInterval $interval) => $interval->type === $type)
            ->sum(fn (OperatorActivityInterval $interval) => $interval->seconds());
    }

    public function totalSeconds(): int
    {
        return (int) collect($this->intervals)
            ->sum(fn (OperatorActivityInterval $interval) => $interval->seconds());
    }

    public function groupByDay(string $timezone = 'Europe/Rome'): array
    {
        $days = [];

        foreach ($this->intervals as $interval) {
            $currentStart = $interval->start->copy();

            while ($currentStart->lt($interval->end)) {
                $localStart = $currentStart->copy()->timezone($timezone);
                $localDayEnd = $localStart->copy()->endOfDay()->timezone('UTC');
                $sliceEnd = $interval->end->copy()->min($localDayEnd);
                $dateKey = $localStart->toDateString();

                $days[$dateKey] ??= [];
                $slice = $interval->withBounds($currentStart->copy(), $sliceEnd);
                if ($slice) {
                    $days[$dateKey][] = $slice;
                }

                $currentStart = $sliceEnd;
            }
        }

        ksort($days);

        return $days;
    }
}
