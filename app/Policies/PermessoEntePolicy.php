<?php

namespace App\Policies;

use App\Models\PermessoEnte;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog as P;

class PermessoEntePolicy
{
    public function view(User $user, PermessoEnte $permesso): bool
    {
        return $user->canViewAllPermessiEnte()
            || ($user->can(P::PERMESSI_ACCESS) && $user->isAssignedToPermessoEnte($permesso));
    }

    public function create(User $user): bool
    {
        return $user->hasAllAbilities(P::PERMESSI_ACCESS, P::PERMESSI_CREATE);
    }

    public function update(User $user, PermessoEnte $permesso): bool
    {
        return $user->can(P::PERMESSI_EDIT) && $this->view($user, $permesso);
    }

    /**
     * Prendere in carico resta un'azione degli operatori Permessi Ente, anche per chi gestisce tutto.
     */
    public function take(User $user, PermessoEnte $permesso): bool
    {
        return $user->hasAllAbilities(P::PERMESSI_ACCESS, P::PERMESSI_OPERATE)
            && ($user->canViewAllPermessiEnte() || $user->isAssignedToPermessoEnte($permesso));
    }

    public function deliver(User $user, PermessoEnte $permesso): bool
    {
        return ($user->hasAllAbilities(P::PERMESSI_ACCESS, P::PERMESSI_OPERATE) && $user->isAssignedToPermessoEnte($permesso))
            || ($user->canManagePermessiEnte() && $this->view($user, $permesso));
    }

    public function complete(User $user, PermessoEnte $permesso): bool
    {
        return $user->canManagePermessiEnte() && $this->view($user, $permesso);
    }

    public function duplicate(User $user, PermessoEnte $permesso): bool
    {
        return $user->canManagePermessiEnte() && $this->create($user) && $this->view($user, $permesso);
    }

    public function assignOperators(User $user): bool
    {
        return $user->canManagePermessiEnte();
    }

    public function delete(User $user, PermessoEnte $permesso): bool
    {
        return $user->can(P::PERMESSI_DELETE) && $this->view($user, $permesso);
    }
}
