<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UsersController extends Controller
{
    public function index(){
        return view('users.index');
    }

    public function accountsTable(){
        return view('users.accounts-table');
    }

    public function create(){
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required | string | max:255',
            'email' => 
                'required | string | email | max:255 | unique:users',
            'password' => 'required | string | min:8 | confirmed'
        ]);

        $user = User::create([
            'name'=> $validated['name'],
            'email'=> $validated['email'],
            'password'=> Hash::make($validated['password'])
        ]);

        $user->assignRole($request->role);

        return redirect()->route('accounts-table')->with('message','Utente creato con successo!');
    }

    public function edit($id){
        $user = User::find($id);
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id){
        $validated = $request->validate([
            'name' => 'required | string | max:255',
            'email' => 'required | string | email | max:255',
            'password'=> 'sometimes | nullable | string | min:8'
            ]);
        $user = User::find($id);
        $user->update([
            'name'=> $validated['name'],
            'email'=> $validated['email'],
            'password'=> !empty($validated["password"]) ? $validated['password'] : $user->password,
        ]);

        $user->syncRoles($request->role);

        return redirect()->route('accounts-table')->with('message','Utente modificato con successo!');

    }

}
