<?php

namespace App\Http\Controllers;

use App\Models\Work;
use App\Exports\WorksExport;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class WorkController extends Controller
{
    public function index(){

        return view('works.index');
    }

    public function create(){

        return view('works.create');
    }

    public function edit($id){
        $work = Work::find($id);

        return view('works.edit', compact('work'));
    }

    public function delete(Request $request, Work $work){
         $work->delete();

        return redirect()->back()->with('success','');
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'date_field' => ['required', Rule::in(['created_at', 'acception_date', 'completion_date'])],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $export = new WorksExport($validated['date_field'], $validated['start'], $validated['end']);

        $filename = sprintf(
            'works_%s_%s_%s.xlsx',
            $validated['date_field'],
            Str::of($validated['start'])->replace([' ',':'], '-'),
            Str::of($validated['end'])->replace([' ',':'], '-')
        );

        return Excel::download($export, $filename);
    }
}
