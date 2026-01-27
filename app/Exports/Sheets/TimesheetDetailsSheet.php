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
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Carbon;
use Carbon\CarbonPeriod;

class TimesheetDetailsSheet implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnFormatting
{
    protected $month;
    protected $year;
    protected $boldRows = [];
    protected $headerRows = [];

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function collection()
    {
        $users = User::role('operator')->orderBy('name')->get();
        $startOfMonth = Carbon::create($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();
        $period = CarbonPeriod::create($startOfMonth, $endOfMonth);

        $collection = collect();
        $currentRow = 2; // Start after headings

        foreach ($users as $user) {
            // USER HEADER ROW
            $collection->push([
                $user->name, // A: Operator Name (merged header conceptually)
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                ''
            ]);
            $this->headerRows[] = $currentRow;
            $currentRow++;

            // Load all timesheets for this user in this month
            $timesheets = Timesheet::where('user_id', $user->id)
                ->whereMonth('date', $this->month)
                ->whereYear('date', $this->year)
                ->get()
                ->keyBy(function ($item) {
                    return $item->date->toDateString();
                });

            foreach ($period as $date) {
                $dateString = $date->toDateString();
                $ts = $timesheets->get($dateString);

                if ($ts) {
                    // Timesheet data exists
                    $dt = fn($d) => $d ? Carbon::parse($d)->timezone('Europe/Rome') : null;
                    $fmtTime = fn($d) => $d ? $d->format('H:i') : '-';

                    $totalMinutes = 0;
                    if ($ts->entry_time && $ts->exit_time) {
                        $totalMinutes = $ts->entry_time->diffInMinutes($ts->exit_time);
                        if ($ts->break_start && $ts->break_end) {
                            $totalMinutes -= $ts->break_start->diffInMinutes($ts->break_end);
                        }
                    }
                    if ($ts->overtime_hours > 0) {
                        $totalMinutes += ($ts->overtime_hours * 60);
                    }

                    $hours = floor($totalMinutes / 60);
                    $mins = $totalMinutes % 60;
                    $totalFormatted = sprintf('%02d:%02d', $hours, $mins);

                    $collection->push([
                        '', // Empty operator column for data rows
                        $dateString, // Date (formatted by columnFormats)
                        $fmtTime($dt($ts->entry_time)),
                        $fmtTime($dt($ts->exit_time)),
                        $fmtTime($dt($ts->break_start)),
                        $fmtTime($dt($ts->break_end)),
                        $totalFormatted,
                        $ts->overtime_hours > 0 ? round($ts->overtime_hours, 2) : '-',
                        $ts->leave_hours > 0 ? round($ts->leave_hours, 2) : '-',
                        $ts->leave_type,
                    ]);
                } else {
                    // No timesheet = Absent
                    $collection->push([
                        '',
                        $dateString, // Date
                        '',
                        '',
                        '',
                        '', // Times
                        'Assente', // Total Hours / Status
                        '',
                        '',
                        '' // Overtime, Leave
                    ]);
                }
                $currentRow++;
            }

            // EMPTY ROW SEPARATOR
            $collection->push(['', '', '', '', '', '', '', '', '', '']);
            $currentRow++;
        }

        return $collection;
    }

    public function headings(): array
    {
        return [
            'Operatore',
            'Data',
            'Entrata',
            'Uscita',
            'Inizio Pausa',
            'Fine Pausa',
            'Totale Ore / Status',
            'Straordinario',
            'Permessi',
            'Tipo Permesso',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $styles = [
            1 => ['font' => ['bold' => true]], // Main Header
        ];

        // Style User Header Rows
        foreach ($this->headerRows as $row) {
            $styles[$row] = [
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ]
            ];
        }

        return $styles;
    }

    public function title(): string
    {
        return 'Dettaglio Giornaliero';
    }
}
