<?php

namespace App\Http\Controllers;

use App\Exports\PermessiEnteExport;
use App\Models\PermessoEnte;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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
            'date_field' => ['required', Rule::in([
                'created_at',
                'consegna',
                'data_fl',
                'data_ra',
                'evaso_dal_dl',
                'mese_saldo',
                'acception_date',
                'delivery_date',
                'completion_date',
            ])],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $export = new PermessiEnteExport($validated['date_field'], $validated['start'], $validated['end']);

        $filename = sprintf(
            'permessi_ente_%s_%s_%s.xlsx',
            $validated['date_field'],
            Str::of($validated['start'])->replace([' ', ':'], '-'),
            Str::of($validated['end'])->replace([' ', ':'], '-')
        );

        return Excel::download($export, $filename);
    }
}
