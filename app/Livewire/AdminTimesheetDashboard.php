<?php

namespace App\Livewire;

use App\Models\Timesheet;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class AdminTimesheetDashboard extends Component
{
    use WithPagination;

    // Detailed View Filters
    public $viewDate; // Specific day
    public $detailedSearch = '';

    // Report View Filters
    public $reportMonth;
    public $reportYear;
    public $reportSearch = '';

    public function mount()
    {
        $this->viewDate = now()->toDateString();
        $this->reportMonth = now()->month;
        $this->reportYear = now()->year;
    }

    // Detailed Table Data
    public function getDetailedTimesheetsProperty()
    {
        $query = Timesheet::with('user')
            ->where('date', $this->viewDate);

        if ($this->detailedSearch) {
            $query->whereHas('user', function ($q) {
                $q->where('name', 'like', '%' . $this->detailedSearch . '%');
            });
        }

        return $query->orderBy('user_id')->get();
    }

    // Report Table Data
    public function getMonthlyReportProperty()
    {
        // Get all users (or filtered)
        $usersQuery = User::role(['operator', 'supervisor']);
        if ($this->reportSearch) {
            $usersQuery->where('name', 'like', '%' . $this->reportSearch . '%');
        }
        $users = $usersQuery->get();

        $report = [];

        foreach ($users as $user) {
            $timesheets = Timesheet::where('user_id', $user->id)
                ->whereMonth('date', $this->reportMonth)
                ->whereYear('date', $this->reportYear)
                ->get();

            $totalMinutes = 0;
            $overtimeMinutes = 0;
            $leaveHours = 0;
            $daysPresent = $timesheets->count();

            foreach ($timesheets as $ts) {
                if ($ts->entry_time && $ts->exit_time) {
                    $mins = $ts->entry_time->diffInMinutes($ts->exit_time);
                    if ($ts->break_start && $ts->break_end) {
                        $mins -= $ts->break_start->diffInMinutes($ts->break_end);
                    }
                    $totalMinutes += $mins;
                }

                if ($ts->overtime_hours > 0) {
                    $overtimeMinutes += ($ts->overtime_hours * 60);
                    $totalMinutes += ($ts->overtime_hours * 60); // Assuming overtime is included in total
                }

                $leaveHours += $ts->leave_hours;
            }

            $report[] = [
                'user' => $user,
                'total_hours' => floor($totalMinutes / 60) . ':' . sprintf('%02d', $totalMinutes % 60),
                'overtime_hours' => floor($overtimeMinutes / 60) . ':' . sprintf('%02d', $overtimeMinutes % 60),
                'leave_hours' => $leaveHours,
                'days_present' => $daysPresent
            ];
        }

        return $report;
    }

    // Navigation for Detailed View
    public function nextPeriod()
    {
        $this->viewDate = Carbon::parse($this->viewDate)->addDay()->toDateString();
    }

    public function previousPeriod()
    {
        $this->viewDate = Carbon::parse($this->viewDate)->subDay()->toDateString();
    }

    public function render()
    {
        return view('livewire.admin-timesheet-dashboard');
    }
}
