<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminTimesheetController extends Controller
{
    public function index()
    {
        return view('admin.timesheets.index');
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2024|max:2030',
        ]);

        $month = $validated['month'];
        $year = $validated['year'];

        $filename = sprintf('timesheets_%02d_%d.xlsx', $month, $year);

        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\TimesheetExport($month, $year), $filename);
    }
}

