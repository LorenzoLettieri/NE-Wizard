<?php

namespace App\Livewire;

use App\Models\Timesheet;
use App\Support\Timesheets\TimesheetStateMachine;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

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
    public $selectedEndDate;

    public function mount()
    {
        $now = now('Europe/Rome');
        $this->weekStartDate = $now->copy()->startOfWeek()->toDateString();
        $this->inputTime = $now->format('H:i');
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
            ->where('date', now('Europe/Rome')->toDateString())
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
        $now = now('Europe/Rome');
        $this->inputTime = $now->format('H:i');
        $this->selectedDate = $now->toDateString();
        $this->selectedEndDate = $now->toDateString();

        $timesheet = $this->todayTimesheet;

        // Reset/pre-fill form fields for leave/overtime
        if (in_array($action, ['leave', 'overtime'])) {
            $this->leaveType = ($timesheet && $action == 'leave') ? $timesheet->leave_type : '';
            $this->leaveHours = ($timesheet && $action == 'leave') ? $timesheet->leave_hours : 0;
            $this->overtimeHours = ($timesheet && $action == 'overtime') ? $timesheet->overtime_hours : 0;
            if ($action === 'leave' && $timesheet?->effectiveLeaveStartTime()) {
                $this->inputTime = $timesheet->effectiveLeaveStartTime()->timezone('Europe/Rome')->format('H:i');
            }
        }

        $this->showModal = true;
    }

    /**
     * Validation rules for the leave/overtime modal, built dynamically
     * from the current action type and (for leaves) the selected leave type.
     */
    protected function leaveOvertimeRules(): array
    {
        if ($this->actionType === 'overtime') {
            return [
                'selectedDate' => 'required|date',
                'overtimeHours' => 'required|numeric|min:0.5|max:24',
            ];
        }

        $rules = [
            'leaveType' => 'required|in:ferie,permesso,malattia',
            'selectedDate' => 'required|date',
        ];

        if ($this->leaveType === 'ferie') {
            $rules['selectedEndDate'] = 'required|date|after_or_equal:selectedDate';
        }

        if (in_array($this->leaveType, ['permesso', 'malattia'], true)) {
            $rules['inputTime'] = 'required|date_format:H:i';
            $rules['leaveHours'] = 'required|numeric|min:0.5|max:24';
        }

        return $rules;
    }

    protected function leaveOvertimeMessages(): array
    {
        return [
            'leaveType.required' => 'Seleziona il tipo di permesso.',
            'leaveType.in' => 'Tipo di permesso non valido.',
            'selectedDate.required' => 'Indica la data.',
            'selectedEndDate.required' => 'Indica la data di fine.',
            'selectedEndDate.after_or_equal' => 'La data di fine deve essere uguale o successiva alla data di inizio.',
            'inputTime.required' => 'Indica l\'orario di inizio.',
            'inputTime.date_format' => 'Orario non valido.',
            'leaveHours.required' => 'Indica le ore di permesso.',
            'leaveHours.min' => 'Le ore di permesso devono essere maggiori di zero.',
            'leaveHours.max' => 'Le ore di permesso non possono superare 24.',
            'overtimeHours.required' => 'Indica le ore di straordinario.',
            'overtimeHours.min' => 'Le ore di straordinario devono essere maggiori di zero.',
            'overtimeHours.max' => 'Le ore di straordinario non possono superare 24.',
        ];
    }

    public function saveAction()
    {
        $this->resetErrorBag();

        // Leave/overtime come from free-form inputs and must be validated.
        // Shift/break actions use "now" and are gated by the state machine below.
        if (in_array($this->actionType, ['leave', 'overtime'], true)) {
            $this->validate($this->leaveOvertimeRules(), $this->leaveOvertimeMessages());

            if ($this->actionType === 'leave' && $this->leaveType === 'ferie') {
                $start = Carbon::parse($this->selectedDate);
                $end = Carbon::parse($this->selectedEndDate);
                if ($start->diffInDays($end) + 1 > 15) {
                    $this->addError('selectedEndDate', 'Il periodo di ferie non può superare 15 giorni.');
                    return;
                }
            }
        }

        $startDate = Carbon::parse($this->selectedDate, 'Europe/Rome')->startOfDay();
        $endDate = ($this->actionType === 'leave' && $this->leaveType === 'ferie')
            ? Carbon::parse($this->selectedEndDate, 'Europe/Rome')->startOfDay()
            : $startDate;

        // For shift/break actions, we always use NOW and override selectedDate just in case
        if (in_array($this->actionType, ['start_shift', 'end_shift', 'start_break', 'end_break'])) {
            $timestamp = now('Europe/Rome')->setTimezone('UTC');
            $dates = [$startDate]; // Only one day for shifts/breaks
        } else {
            // For leaves/overtime, we use the date range (only if leaveType is 'ferie')
            $timestamp = now('Europe/Rome')->setTimezone('UTC');

            $dates = [];
            $currentDate = $startDate->copy();

            // Loop logic
            do {
                if (count($dates) >= 15)
                    break;
                $dates[] = $currentDate->copy();
                $currentDate->addDay();
            } while ($currentDate->lte($endDate));
        }

        foreach ($dates as $date) {
            $timesheet = Timesheet::where('user_id', Auth::id())
                ->where('date', $date->toDateString())
                ->first();

            $transition = app(TimesheetStateMachine::class)->validate($this->actionType, $timesheet);
            if (! $transition->allowed) {
                $this->addError('actionType', $transition->message());
                return;
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
                    $this->normalizeLegacyHourlyLeaveOnly($timesheet);
                    if (!$timesheet->effectiveShiftEntryTime())
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
                    $wasLegacyHourlyLeaveOnly = $timesheet->isLegacyHourlyLeaveOnly();
                    $timesheet->leave_type = $this->leaveType;
                    if ($this->leaveType === 'ferie') {
                        $timesheet->leave_hours = 8;
                        $timesheet->leave_start_time = null;
                    } else {
                        $timesheet->leave_hours = $this->leaveHours;
                        if ($this->inputTime) {
                            $timesheet->leave_start_time = Carbon::createFromFormat('Y-m-d H:i', $date->toDateString() . ' ' . $this->inputTime, 'Europe/Rome')
                                ->setTimezone('UTC');
                        }
                        if (!$timesheet->exit_time && !$timesheet->break_start && !$timesheet->break_end && $wasLegacyHourlyLeaveOnly) {
                            $timesheet->entry_time = null;
                        }
                    }
                    break;
                case 'overtime':
                    $timesheet->overtime_hours = $this->overtimeHours;
                    break;
            }

            $timesheet->save();
        }

        $this->showModal = false;
        $this->dispatch('timesheet-updated');
    }

    private function normalizeLegacyHourlyLeaveOnly(Timesheet $timesheet): void
    {
        if (! $timesheet->isLegacyHourlyLeaveOnly()) {
            return;
        }

        $timesheet->leave_start_time = $timesheet->entry_time;
        $timesheet->entry_time = null;
    }

    public function render()
    {
        return view('livewire.operator-timesheet');
    }
}
