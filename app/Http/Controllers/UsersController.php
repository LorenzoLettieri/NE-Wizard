<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index()
    {
        return view('users.index');
    }

    public function accountsTable()
    {
        return view('users.accounts-table');
    }

    public function create()
    {
        $roles = Role::all();
        $companies = Company::all();
        return view('users.create', compact('roles', 'companies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required | string | max:255',
            'email' =>
                'required | string | email | max:255 | unique:users',
            'password' => 'required | string | min:8 | confirmed',
            'company_id' => 'nullable | exists:companies,id',
            'roles' => 'required | array',
            'roles.*' => 'exists:roles,name'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company_id' => $validated['company_id']
        ]);

        $user->syncRoles($validated['roles']);

        return redirect()->route('accounts-table')->with('message', 'Utente creato con successo!');
    }

    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::all();
        $companies = Company::all();
        return view('users.edit', compact('user', 'roles', 'companies'));
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required | string | max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($id),
            ],
            'password' => 'sometimes | nullable | string | min:8 | confirmed',
            'company_id' => 'nullable | exists:companies,id',
            'roles' => 'required | array',
            'roles.*' => 'exists:roles,name'
        ]);

        $user = User::findOrFail($id);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_id' => $validated['company_id'] ?? null
        ];

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);

        $user->syncRoles($validated['roles']);

        return redirect()->route('accounts-table')->with('message', 'Utente modificato con successo!');

    }

}
