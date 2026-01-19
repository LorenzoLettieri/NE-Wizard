<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class OperatorTimesheetController extends Controller
{
    public function index()
    {
        return view('operator.timesheet');
    }
}
