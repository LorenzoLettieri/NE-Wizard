<?php

namespace App\Policies;

use App\Models\PermessoEnte;
use App\Models\User;

class PermessoEntePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Prendere in carico resta un'azione degli operatori Permessi Ente, anche per gli admin
        if ($ability === 'take') {
            return null;
        }

        return $user->canManageAllPermessiEnte() ? true : null;
    }

    public function view(User $user, PermessoEnte $permesso): bool
    {
        return $user->isAssignedToPermessoEnte($permesso);
    }

    public function update(User $user, PermessoEnte $permesso): bool
    {
        return $user->isAssignedToPermessoEnte($permesso);
    }

    public function take(User $user, PermessoEnte $permesso): bool
    {
        return $user->hasRole('permessi ente')
            && ($user->canManageAllPermessiEnte() || $user->isAssignedToPermessoEnte($permesso));
    }

    public function deliver(User $user, PermessoEnte $permesso): bool
    {
        return $user->isAssignedToPermessoEnte($permesso);
    }

    public function complete(User $user, PermessoEnte $permesso): bool
    {
        return false;
    }

    public function duplicate(User $user, PermessoEnte $permesso): bool
    {
        return false;
    }

    public function assignOperators(User $user): bool
    {
        return false;
    }

    public function delete(User $user, PermessoEnte $permesso): bool
    {
        return false;
    }
}
