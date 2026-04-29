<?php

namespace Tests\Feature;

use App\Livewire\OperatorTimesheet;
use App\Models\Timesheet;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class OperatorTimesheetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_break_is_rejected_when_no_break_is_open(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Timesheet::create([
            'user_id' => $operator->id,
            'date' => now()->toDateString(),
            'entry_time' => now()->startOfDay()->addHours(8),
        ]);

        $this->actingAs($operator);

        Livewire::test(OperatorTimesheet::class)
            ->set('actionType', 'end_break')
            ->set('selectedDate', now()->toDateString())
            ->call('saveAction')
            ->assertHasErrors(['actionType']);
    }

    public function test_hourly_leave_uses_dedicated_leave_start_without_opening_shift(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-10 09:00:00', 'Europe/Rome'));

        try {
            $this->seed(RoleSeeder::class);

            $operator = User::factory()->create();
            $operator->assignRole('operator');

            $this->actingAs($operator);

            Livewire::test(OperatorTimesheet::class)
                ->set('actionType', 'leave')
                ->set('leaveType', 'permesso')
                ->set('leaveHours', 2)
                ->set('inputTime', '10:00')
                ->set('selectedDate', '2026-04-10')
                ->set('selectedEndDate', '2026-04-10')
                ->call('saveAction')
                ->assertHasNoErrors();

            $timesheet = Timesheet::where('user_id', $operator->id)->firstOrFail();

            $this->assertNull($timesheet->entry_time);
            $this->assertSame('2026-04-10 08:00:00', $timesheet->leave_start_time->format('Y-m-d H:i:s'));
            $this->assertSame('permesso', $timesheet->leave_type);
            $this->assertSame('2.00', (string) $timesheet->leave_hours);
        } finally {
            Carbon::setTestNow();
        }
    }
}
