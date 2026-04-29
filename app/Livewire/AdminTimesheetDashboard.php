<?php

namespace App\Livewire;

use App\Domain\OperatorActivity\OperatorActivityBuilder;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
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

    // Edit modal state
    public $showEditModal = false;
    public $editingTimesheetId = null;
    public $editUserName = '';
    public $editDate;
    public $editEntryTime = '';
    public $editExitTime = '';
    public $editBreakStart = '';
    public $editBreakEnd = '';
    public $editLeaveStartTime = '';
    public $editLeaveType = '';
    public $editLeaveHours = 0;
    public $editOvertimeHours = 0;

    public function mount()
    {
        $now = now('Europe/Rome');
        $this->viewDate = $now->toDateString();
        $this->reportMonth = $now->month;
        $this->reportYear = $now->year;
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
        $usersQuery = User::role('operator');
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

            $windowStart = Carbon::create((int) $this->reportYear, (int) $this->reportMonth, 1, 0, 0, 0, 'Europe/Rome')
                ->startOfMonth()
                ->setTimezone('UTC');
            $windowEnd = Carbon::create((int) $this->reportYear, (int) $this->reportMonth, 1, 0, 0, 0, 'Europe/Rome')
                ->endOfMonth()
                ->setTimezone('UTC');

            $works = $user->works()
                ->where(function ($query) use ($windowStart, $windowEnd) {
                    $query->whereBetween('user_work.created_at', [$windowStart, $windowEnd])
                        ->orWhere(function ($nested) use ($windowStart, $windowEnd) {
                            $nested->whereNotNull('acception_date')
                                ->where('acception_date', '<=', $windowEnd)
                                ->where(function ($window) use ($windowStart) {
                                    $window->whereNull('delivery_date')
                                        ->orWhere('delivery_date', '>=', $windowStart);
                                });
                        });
                })
                ->with('workSuspensions')
                ->get();

            $summary = app(OperatorActivityBuilder::class)->build($user, $works, $timesheets, $windowStart, $windowEnd);
            $daysPresent = collect($summary->dailyBreakdown())
                ->filter(fn (array $day) => $day['presence_seconds'] > 0 || $day['leave_seconds'] > 0 || $day['overtime_seconds'] > 0)
                ->count();

            $report[] = [
                'user' => $user,
                'total_hours' => Work::formatDuration($summary->presenceSeconds()) ?? '0m',
                'presence_hours' => Work::formatDuration($summary->presenceSeconds()) ?? '0m',
                'active_work_hours' => Work::formatDuration($summary->activeWorkSeconds()) ?? '0m',
                'break_hours' => Work::formatDuration($summary->breakSeconds()) ?? '0m',
                'overtime_hours' => Work::formatDuration($summary->overtimeSeconds()) ?? '0m',
                'leave_hours' => round($summary->leaveSeconds() / 3600, 2),
                'days_present' => $daysPresent,
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

    public function openEditModal($timesheetId)
    {
        $timesheet = Timesheet::with('user')->findOrFail($timesheetId);

        $this->editingTimesheetId = $timesheet->id;
        $this->editUserName = $timesheet->user->name;
        $this->editDate = $timesheet->date->toDateString();
        $this->editEntryTime = $this->formatTimeForInput($timesheet->effectiveShiftEntryTime());
        $this->editExitTime = $this->formatTimeForInput($timesheet->exit_time);
        $this->editBreakStart = $this->formatTimeForInput($timesheet->break_start);
        $this->editBreakEnd = $this->formatTimeForInput($timesheet->break_end);
        $this->editLeaveStartTime = $this->formatTimeForInput($timesheet->effectiveLeaveStartTime());
        $this->editLeaveType = $timesheet->leave_type ?? '';
        $this->editLeaveHours = (float) $timesheet->leave_hours;
        $this->editOvertimeHours = (float) $timesheet->overtime_hours;

        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->editingTimesheetId = null;
        $this->editUserName = '';
        $this->editDate = null;
        $this->editEntryTime = '';
        $this->editExitTime = '';
        $this->editBreakStart = '';
        $this->editBreakEnd = '';
        $this->editLeaveStartTime = '';
        $this->editLeaveType = '';
        $this->editLeaveHours = 0;
        $this->editOvertimeHours = 0;
        $this->resetValidation();
    }

    public function updatedEditLeaveType($value)
    {
        if ($value === 'ferie' && (float) $this->editLeaveHours <= 0) {
            $this->editLeaveHours = 8;
        }

        if ($value === '') {
            $this->editLeaveHours = 0;
        }
    }

    public function saveEdit()
    {
        $this->validate([
            'editingTimesheetId' => 'required|integer|exists:timesheets,id',
            'editDate' => 'required|date',
            'editEntryTime' => 'nullable|date_format:H:i',
            'editExitTime' => 'nullable|date_format:H:i',
            'editBreakStart' => 'nullable|date_format:H:i',
            'editBreakEnd' => 'nullable|date_format:H:i',
            'editLeaveStartTime' => 'nullable|date_format:H:i',
            'editLeaveType' => 'nullable|in:ferie,permesso,malattia',
            'editLeaveHours' => 'nullable|numeric|min:0|max:24',
            'editOvertimeHours' => 'nullable|numeric|min:0|max:24',
        ]);

        $entryTime = $this->parseEditTime($this->editEntryTime);
        $exitTime = $this->parseEditTime($this->editExitTime);
        $breakStart = $this->parseEditTime($this->editBreakStart);
        $breakEnd = $this->parseEditTime($this->editBreakEnd);
        $leaveStartTime = $this->parseEditTime($this->editLeaveStartTime);

        if ($exitTime && !$entryTime) {
            $this->addError('editExitTime', 'Per impostare l\'uscita è necessario impostare anche l\'entrata.');
        }

        if ($entryTime && $exitTime && $exitTime->lt($entryTime)) {
            $this->addError('editExitTime', 'L\'orario di uscita deve essere successivo all\'entrata.');
        }

        if ($breakStart && !$entryTime) {
            $this->addError('editBreakStart', 'Per inserire una pausa è necessario impostare l\'entrata.');
        }

        if ($breakEnd && !$breakStart) {
            $this->addError('editBreakEnd', 'Per chiudere la pausa è necessario indicare anche l\'inizio pausa.');
        }

        if ($breakStart && $entryTime && $breakStart->lt($entryTime)) {
            $this->addError('editBreakStart', 'L\'inizio pausa non può essere precedente all\'entrata.');
        }

        if ($breakStart && $breakEnd && $breakEnd->lt($breakStart)) {
            $this->addError('editBreakEnd', 'La fine pausa deve essere successiva all\'inizio pausa.');
        }

        if ($breakEnd && $exitTime && $breakEnd->gt($exitTime)) {
            $this->addError('editBreakEnd', 'La fine pausa non può essere successiva all\'uscita.');
        }

        if ($this->editLeaveType === '' && (float) $this->editLeaveHours > 0) {
            $this->addError('editLeaveType', 'Se inserisci ore di permesso devi indicare anche il tipo.');
        }

        if (in_array($this->editLeaveType, ['permesso', 'malattia'], true) && (float) $this->editLeaveHours <= 0) {
            $this->addError('editLeaveHours', 'Inserisci un numero di ore maggiore di zero.');
        }

        if (in_array($this->editLeaveType, ['permesso', 'malattia'], true) && ! $leaveStartTime) {
            $this->addError('editLeaveStartTime', 'Indica l\'orario di inizio del permesso.');
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $leaveHours = (float) $this->editLeaveHours;
        if ($this->editLeaveType === 'ferie' && $leaveHours <= 0) {
            $leaveHours = 8;
        }
        if ($this->editLeaveType === '') {
            $leaveHours = 0;
            $leaveStartTime = null;
        }
        if ($this->editLeaveType === 'ferie') {
            $leaveStartTime = null;
        }

        $timesheet = Timesheet::findOrFail($this->editingTimesheetId);
        $timesheet->date = $this->editDate;
        $timesheet->entry_time = $entryTime;
        $timesheet->exit_time = $exitTime;
        $timesheet->break_start = $breakStart;
        $timesheet->break_end = $breakEnd;
        $timesheet->leave_start_time = $leaveStartTime;
        $timesheet->leave_type = $this->editLeaveType ?: null;
        $timesheet->leave_hours = $leaveHours;
        $timesheet->overtime_hours = (float) $this->editOvertimeHours;
        $timesheet->save();

        $this->closeEditModal();
        session()->flash('timesheetSuccess', 'Timesheet aggiornato con successo.');
    }

    private function formatTimeForInput($dateTime): string
    {
        if (!$dateTime) {
            return '';
        }

        return $dateTime->timezone('Europe/Rome')->format('H:i');
    }

    private function parseEditTime($time): ?Carbon
    {
        if (!$time) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d H:i', $this->editDate . ' ' . $time, 'Europe/Rome')->setTimezone('UTC');
    }

    public function render()
    {
        return view('livewire.admin-timesheet-dashboard');
    }
}
