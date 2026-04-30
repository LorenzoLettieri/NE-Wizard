<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class OperatorWorkspaceController extends Controller
{
    public function show(User $user)
    {
        abort_unless($user->can('get works'), 404);

        return view('admin.operators.show', [
            'operator' => $user,
        ]);
    }
}
