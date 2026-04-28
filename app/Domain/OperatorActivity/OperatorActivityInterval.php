<?php

namespace App\Domain\OperatorActivity;

use Carbon\Carbon;

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

    public function overlaps(self $other): bool
    {
        return $this->start->lt($other->end) && $this->end->gt($other->start);
    }

    public function touches(self $other): bool
    {
        return $this->end->eq($other->start) || $this->start->eq($other->end);
    }

    public function withBounds(Carbon $start, Carbon $end): ?self
    {
        if ($end->lessThanOrEqualTo($start)) {
            return null;
        }

        return new self($this->type, $this->label, $start, $end, $this->meta);
    }
}
