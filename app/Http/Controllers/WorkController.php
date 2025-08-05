<?php

namespace App\Http\Controllers;

use App\Models\Work;
use Illuminate\Http\Request;

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
}
