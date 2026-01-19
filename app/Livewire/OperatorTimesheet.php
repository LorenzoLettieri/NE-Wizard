<?php

namespace App\Livewire;

use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class OperatorTimesheet extends Component
{
    public $inputTime;
    public $weekStartDate;
    public $actionType; // 'start_shift', 'start_break', 'end_break', 'end_shift', 'leave', 'overtime'
    public $showModal = false;

    // For leaves/overtime form
    public $leaveType;
    public $leaveHours;
    public $overtimeHours;

    public function mount()
    {
        $this->weekStartDate = now()->startOfWeek()->toDateString();
        $this->inputTime = now()->format('H:i');
    }

    public function getTimesheetsProperty()
    {
        return Timesheet::where('user_id', Auth::id())
            ->whereBetween('date', [
                Carbon::parse($this->weekStartDate)->startOfWeek(),
                Carbon::parse($this->weekStartDate)->endOfWeek()
            ])
            ->orderBy('date', 'desc')
            ->get();
    }

    public function getTodayTimesheetProperty()
    {
        return Timesheet::where('user_id', Auth::id())
            ->where('date', now()->toDateString())
            ->first();
    }

    public function nextWeek()
    {
        $this->weekStartDate = Carbon::parse($this->weekStartDate)->addWeek()->toDateString();
    }

    public function previousWeek()
    {
        $this->weekStartDate = Carbon::parse($this->weekStartDate)->subWeek()->toDateString();
    }

    public function openActionModal($action)
    {
        $this->actionType = $action;
        $timesheet = $this->todayTimesheet;
        $existingTime = null;

        if ($timesheet) {
            switch ($action) {
                case 'start_shift':
                    $existingTime = $timesheet->entry_time;
                    break;
                case 'end_shift':
                    $existingTime = $timesheet->exit_time;
                    break;
                case 'start_break':
                    $existingTime = $timesheet->break_start;
                    break;
                case 'end_break':
                    $existingTime = $timesheet->break_end;
                    break;
            }
        }

        $this->inputTime = $existingTime ? $existingTime->format('H:i') : now()->format('H:i');

        // Reset form fields
        $this->leaveType = ($timesheet && $action == 'leave') ? $timesheet->leave_type : '';
        $this->leaveHours = ($timesheet && $action == 'leave') ? $timesheet->leave_hours : 0;
        $this->overtimeHours = ($timesheet && $action == 'overtime') ? $timesheet->overtime_hours : 0;

        $this->showModal = true;
    }

    public function saveAction()
    {
        $time = Carbon::createFromFormat('H:i', $this->inputTime);
        // Combine with today's date for timestamp
        $timestamp = now()->setTime($time->hour, $time->minute, 0);

        $timesheet = $this->todayTimesheet;

        if (!$timesheet && in_array($this->actionType, ['start_shift'])) {
            $timesheet = new Timesheet();
            $timesheet->user_id = Auth::id();
            $timesheet->date = Carbon::today();
        }

        // Safety check if timesheet doesn't exist for other actions (though UI shouldn't allow it)
        if (!$timesheet && !in_array($this->actionType, ['start_shift'])) {
            $timesheet = Timesheet::firstOrCreate([
                'user_id' => Auth::id(),
                'date' => Carbon::today()
            ]);
        }

        switch ($this->actionType) {
            case 'start_shift':
                $timesheet->entry_time = $timestamp;
                break;
            case 'end_shift':
                $timesheet->exit_time = $timestamp;
                break;
            case 'start_break':
                $timesheet->break_start = $timestamp;
                break;
            case 'end_break':
                $timesheet->break_end = $timestamp;
                break;
            case 'leave':
                $timesheet->leave_type = $this->leaveType;
                $timesheet->leave_hours = $this->leaveHours;
                break;
            case 'overtime':
                $timesheet->overtime_hours = $this->overtimeHours;
                break;
        }

        $timesheet->save();
        $this->showModal = false;
        $this->dispatch('timesheet-updated'); // Optional: for UI feedback
    }

    public function render()
    {
        return view('livewire.operator-timesheet');
    }
}
