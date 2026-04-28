<?php

namespace Tests\Feature;

use App\Livewire\OperatorTimesheet;
use App\Models\Timesheet;
use App\Models\User;
use Database\Seeders\RoleSeeder;
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
}
