<?php

namespace App\Exports\Sheets;

use App\Models\User;
use App\Models\Timesheet;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TimesheetRecapSheet implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles, WithTitle
{
    protected $month;
    protected $year;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        $users = User::role(['operator', 'supervisor'])->get();
        $report = collect();

        foreach ($users as $user) {
            $timesheets = Timesheet::where('user_id', $user->id)
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
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
                    $totalMinutes += ($ts->overtime_hours * 60);
                }

                $leaveHours += $ts->leave_hours;
            }

            // Only add if there is activity or if needed (here adding all operators)
            $report->push([
                'user_name' => $user->name,
                'days_present' => $daysPresent,
                'leave_hours' => $leaveHours,
                'overtime_hours' => floor($overtimeMinutes / 60) . ':' . sprintf('%02d', $overtimeMinutes % 60),
                'total_hours' => floor($totalMinutes / 60) . ':' . sprintf('%02d', $totalMinutes % 60),
            ]);
        }

        return $report;
    }

    public function headings(): array
    {
        return [
            'Operatore',
            'Giorni Presenza',
            'Ore Permesso',
            'Ore Straordinario',
            'Totale Ore Lavorate',
        ];
    }

    public function map($row): array
    {
        return [
            $row['user_name'],
            $row['days_present'],
            $row['leave_hours'],
            $row['overtime_hours'],
            $row['total_hours'],
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function title(): string
    {
        return 'Riepilogo Mensile';
    }
}
