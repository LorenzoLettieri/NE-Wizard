<?php
namespace App\Http\Controllers;

use App\Models\Gbx;
use Illuminate\Http\Request;

class GbxController extends Controller
{
    public function index()
    {
        return view('gbxes.index');
    }

    public function create()
    {
        return view('gbxes.create');
    }

    public function delete(Request $request, Gbx $gbx)
    {
        $gbx->delete();
        return redirect()->back()->with('success', 'Gbx eliminato con successo!');
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'date_field' => 'required',
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $export = new \App\Exports\GbxExport($validated['date_field'], $validated['start'], $validated['end']);

        $filename = sprintf(
            'gbxes_%s_%s_%s.xlsx',
            $validated['date_field'],
            \Illuminate\Support\Str::of($validated['start'])->replace([' ', ':'], '-'),
            \Illuminate\Support\Str::of($validated['end'])->replace([' ', ':'], '-')
        );

        return \Maatwebsite\Excel\Facades\Excel::download($export, $filename);
    }
}