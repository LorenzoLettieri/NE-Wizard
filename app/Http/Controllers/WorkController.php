<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(){

        return view('works.index');
    }

    public function create(){

        return view('works.create');
    }
}
