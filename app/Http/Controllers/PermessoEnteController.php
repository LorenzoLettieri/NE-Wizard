<?php

namespace App\Http\Controllers;

use App\Models\PermessoEnte;
use Illuminate\Http\Request;

class PermessoEnteController extends Controller
{
    public function index()
    {
        $permessi = PermessoEnte::all();
        return view('permessi_ente.index', compact('permessi'));
    }

        public function create(){

        return view('permessi_ente.create');
    }
}
