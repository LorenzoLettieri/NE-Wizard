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
}