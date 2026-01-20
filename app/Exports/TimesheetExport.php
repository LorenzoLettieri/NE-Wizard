<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use App\Exports\Sheets\TimesheetRecapSheet;
use App\Exports\Sheets\TimesheetDetailsSheet;

class TimesheetExport implements WithMultipleSheets
{
    protected $month;
    protected $year;

    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }

    public function sheets(): array
    {
        return [
            new TimesheetRecapSheet($this->month, $this->year),
            new TimesheetDetailsSheet($this->month, $this->year),
        ];
    }
}
