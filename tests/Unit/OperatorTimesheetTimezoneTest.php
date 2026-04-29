<?php

namespace Tests\Unit;

use App\Livewire\OperatorTimesheet;
use Carbon\Carbon;
use Tests\TestCase;

class OperatorTimesheetTimezoneTest extends TestCase
{
    public function test_mount_uses_rome_timezone_for_current_date_and_time(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 00:30:00', 'Europe/Rome'));

        try {
            $component = new OperatorTimesheet();
            $component->mount();

            $this->assertSame('2026-04-06', $component->weekStartDate);
            $this->assertSame('00:30', $component->inputTime);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_mysql_connections_are_configured_to_store_timestamps_in_utc(): void
    {
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+00:00', config('database.connections.mariadb.timezone'));
    }
}
