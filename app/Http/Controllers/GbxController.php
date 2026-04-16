<?php
namespace App\Http\Controllers;

use App\Exports\GbxExport;
use App\Models\Gbx;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

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
            'date_field' => ['required', Rule::in([
                'date',
                'created_at',
                'appointment_date',
                'inspection_date',
                'verbal_date',
                'obligation_date',
                'release_date',
                'permission_request_date',
                'permission_obtain_date',
                'project_date',
                'speedark_date',
                'cart_update_date',
            ])],
            'start' => ['required', 'date'],
            'end' => ['required', 'date', 'after_or_equal:start'],
        ]);

        $export = new GbxExport($validated['date_field'], $validated['start'], $validated['end']);

        $filename = sprintf(
            'gbxes_%s_%s_%s.xlsx',
            $validated['date_field'],
            Str::of($validated['start'])->replace([' ', ':'], '-'),
            Str::of($validated['end'])->replace([' ', ':'], '-')
        );

        return Excel::download($export, $filename);
    }
}
