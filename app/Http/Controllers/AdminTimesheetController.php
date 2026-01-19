<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminTimesheetController extends Controller
{
    public function index()
    {
        return view('admin.timesheets.index');
    }
}
