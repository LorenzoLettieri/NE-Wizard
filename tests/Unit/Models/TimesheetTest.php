<?php

namespace Tests\Unit\Models;

use App\Models\Timesheet;
use Carbon\Carbon;
use Tests\TestCase;

class TimesheetTest extends TestCase
{
    public function test_legacy_hourly_leave_entry_is_not_treated_as_shift_entry(): void
    {
        $timesheet = Timesheet::make([
            'entry_time' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
            'exit_time' => null,
            'leave_type' => 'permesso',
            'leave_hours' => 2,
        ]);

        $this->assertTrue($timesheet->isLegacyHourlyLeaveOnly());
        $this->assertNull($timesheet->effectiveShiftEntryTime());
        $this->assertSame(
            '2026-04-10 10:00:00',
            $timesheet->effectiveLeaveStartTime()->format('Y-m-d H:i:s'),
        );
    }

    public function test_dedicated_hourly_leave_start_takes_precedence_over_legacy_entry(): void
    {
        $timesheet = Timesheet::make([
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
            'leave_start_time' => Carbon::parse('2026-04-10 14:00:00', 'UTC'),
            'leave_type' => 'permesso',
            'leave_hours' => 1,
        ]);

        $this->assertFalse($timesheet->isLegacyHourlyLeaveOnly());
        $this->assertSame(
            '2026-04-10 08:00:00',
            $timesheet->effectiveShiftEntryTime()->format('Y-m-d H:i:s'),
        );
        $this->assertSame(
            '2026-04-10 14:00:00',
            $timesheet->effectiveLeaveStartTime()->format('Y-m-d H:i:s'),
        );
    }
}
