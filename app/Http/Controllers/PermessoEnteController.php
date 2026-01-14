<?php

namespace App\Http\Controllers;

use App\Models\PermessoEnte;
use Illuminate\Http\Request;

class PermessoEnteController extends Controller
{
    public function index()
    {
        return view('permessi_ente.index');
    }

    public function create()
    {
        return view('permessi_ente.create');
    }

    public function edit($id)
    {
        $permesso = PermessoEnte::findOrFail($id);
        return view('permessi_ente.edit', compact('permesso'));
    }

    public function delete(Request $request, PermessoEnte $permesso)
    {
        $permesso->delete();
        return redirect()->back()->with('success', 'Permesso eliminato con successo');
    }
}
