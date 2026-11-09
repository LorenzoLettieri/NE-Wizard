<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\User;
use Livewire\Component;

class UserTable extends Component
{
    use AuthorizesComponentAccess;

    public $users;

    // Gestione account: solo super admin
    protected function canAccessComponent(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        // Un super admin non si elimina da qui, né per errore né da una richiesta costruita a mano
        abort_if($user->isSuperAdmin() || $user->is(auth()->user()), 403);

        $user->delete();
    }

    public function render()
    {
        $this->users = User::with('company')->get();
        return view('livewire.user-table');
    }
}
