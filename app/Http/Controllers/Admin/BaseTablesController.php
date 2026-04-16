<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class BaseTablesController extends Controller
{
    public function index()
    {
        return view('admin.base-tables.index');
    }
}
