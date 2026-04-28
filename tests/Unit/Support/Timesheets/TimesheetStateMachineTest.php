<?php

namespace Tests\Unit\Support\Timesheets;

use App\Models\Timesheet;
use App\Support\Timesheets\TimesheetStateMachine;
use Carbon\Carbon;
use Tests\TestCase;

class TimesheetStateMachineTest extends TestCase
{
    public function test_state_machine_rejects_shift_end_without_shift_start(): void
    {
        $result = app(TimesheetStateMachine::class)->validate(
            action: 'end_shift',
            timesheet: new Timesheet(),
        );

        $this->assertFalse($result->allowed);
        $this->assertSame('shift_not_started', $result->reason);
    }

    public function test_state_machine_allows_closing_open_break(): void
    {
        $timesheet = new Timesheet([
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'break_start' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
        ]);

        $result = app(TimesheetStateMachine::class)->validate('end_break', $timesheet);

        $this->assertTrue($result->allowed);
        $this->assertNull($result->reason);
    }
}
