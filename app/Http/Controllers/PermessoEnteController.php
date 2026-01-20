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

    public function download(Request $request)
    {
        $validated = $request->validate([
            'date_field' => 'required',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $export = new \App\Exports\PermessiEnteExport($validated['date_field'], $validated['start'], $validated['end']);

        $filename = sprintf(
            'permessi_ente_%s_%s_%s.xlsx',
            $validated['date_field'],
            \Illuminate\Support\Str::of($validated['start'])->replace([' ', ':'], '-'),
            \Illuminate\Support\Str::of($validated['end'])->replace([' ', ':'], '-')
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}

