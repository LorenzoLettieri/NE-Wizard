<?php

namespace App\Http\Controllers;

use App\Exports\DecommissioningExport;
use App\Models\Decommissioning;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class DecommissioningController extends Controller
{
    public function index()
    {
        return view('decommissionings.index');
    }

    public function create()
    {
        return view('decommissionings.create');
    }

    public function edit($id)
    {
        $decommissioning = Decommissioning::findOrFail($id);

        return view('decommissionings.edit', compact('decommissioning'));
    }

    public function delete(Request $request, Decommissioning $decommissioning)
    {
        $decommissioning->delete();

        return redirect()->back()->with('success', 'Decommissioning eliminato con successo!');
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'date_field' => ['required', Rule::in(['created_at', 'fl_permesso', 'data_il', 'data_fl'])],
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $export = new DecommissioningExport(
            $validated['date_field'],
            $validated['start'],
            $validated['end']
        );

        $filename = sprintf(
            'decommissionings_%s_%s_%s.xlsx',
            $validated['date_field'],
            \Illuminate\Support\Str::of($validated['start'])->replace([' ', ':'], '-'),
            \Illuminate\Support\Str::of($validated['end'])->replace([' ', ':'], '-')
        );

        return Excel::download($export, $filename);
    }
}
