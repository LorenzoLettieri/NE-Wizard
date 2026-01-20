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
    public $selectedDate;

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
        $this->inputTime = now()->format('H:i');
        $this->selectedDate = now()->toDateString();

        $timesheet = $this->todayTimesheet;

        // Reset/pre-fill form fields for leave/overtime
        if (in_array($action, ['leave', 'overtime'])) {
            $this->leaveType = ($timesheet && $action == 'leave') ? $timesheet->leave_type : '';
            $this->leaveHours = ($timesheet && $action == 'leave') ? $timesheet->leave_hours : 0;
            $this->overtimeHours = ($timesheet && $action == 'overtime') ? $timesheet->overtime_hours : 0;
        }

        $this->showModal = true;
    }

    public function saveAction()
    {
        $date = Carbon::parse($this->selectedDate);
        $timesheet = Timesheet::where('user_id', Auth::id())
            ->where('date', $date->toDateString())
            ->first();

        // For shift/break actions, we always use NOW and override selectedDate just in case
        if (in_array($this->actionType, ['start_shift', 'end_shift', 'start_break', 'end_break'])) {
            $timestamp = now();
            $date = Carbon::today();
        } else {
            // For leaves/overtime, we use the selectedDate
            $timestamp = now(); // Timestamp here is less critical, it's the date that matters
        }

        if (!$timesheet && $this->actionType === 'start_shift') {
            $timesheet = new Timesheet();
            $timesheet->user_id = Auth::id();
            $timesheet->date = $date;
        }

        // Safety check if timesheet doesn't exist for other actions
        if (!$timesheet) {
            $timesheet = Timesheet::firstOrCreate([
                'user_id' => Auth::id(),
                'date' => $date
            ]);
        }

        switch ($this->actionType) {
            case 'start_shift':
                if (!$timesheet->entry_time)
                    $timesheet->entry_time = $timestamp;
                break;
            case 'end_shift':
                if (!$timesheet->exit_time)
                    $timesheet->exit_time = $timestamp;
                break;
            case 'start_break':
                if (!$timesheet->break_start)
                    $timesheet->break_start = $timestamp;
                break;
            case 'end_break':
                if (!$timesheet->break_end)
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
        $this->dispatch('timesheet-updated');
    }

    public function render()
    {
        return view('livewire.operator-timesheet');
    }
}
