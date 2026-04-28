<?php

namespace App\Support\Timesheets;

use App\Models\Timesheet;

final class TimesheetStateMachine
{
    public function validate(string $action, ?Timesheet $timesheet): TimesheetTransitionResult
    {
        return match ($action) {
            'start_shift' => TimesheetTransitionResult::allowWhen(! $timesheet?->entry_time, 'shift_already_started'),
            'start_break' => TimesheetTransitionResult::allowWhen(
                (bool) ($timesheet?->entry_time && ! $timesheet?->break_start && ! $timesheet?->exit_time),
                'break_not_allowed'
            ),
            'end_break' => TimesheetTransitionResult::allowWhen(
                (bool) ($timesheet?->break_start && ! $timesheet?->break_end && ! $timesheet?->exit_time),
                'break_not_open'
            ),
            'end_shift' => TimesheetTransitionResult::allowWhen(
                (bool) ($timesheet?->entry_time && ! $timesheet?->exit_time),
                'shift_not_started'
            ),
            default => TimesheetTransitionResult::allowed(),
        };
    }
}

final class TimesheetTransitionResult
{
    public function __construct(
        public readonly bool $allowed,
        public readonly ?string $reason = null,
    ) {
    }

    public static function allowed(): self
    {
        return new self(true);
    }

    public static function allowWhen(bool $condition, string $reason): self
    {
        return $condition ? self::allowed() : new self(false, $reason);
    }

    public function message(): string
    {
        return match ($this->reason) {
            'shift_already_started' => 'Il turno risulta già aperto.',
            'break_not_allowed' => 'Non puoi aprire una pausa senza un turno attivo.',
            'break_not_open' => 'Non puoi chiudere una pausa che non risulta aperta.',
            'shift_not_started' => 'Non puoi chiudere un turno che non risulta aperto.',
            default => 'Operazione non consentita per lo stato attuale del timesheet.',
        };
    }
}
