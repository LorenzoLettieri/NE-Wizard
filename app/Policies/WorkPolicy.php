<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;
use App\Support\Authorization\PermissionCatalog as P;

class WorkPolicy
{
    public function view(User $user, Work $work): bool
    {
        return $user->canViewAllWorks()
            || ($user->isWorksOperator() && $user->isAssignedToWork($work));
    }

    public function create(User $user): bool
    {
        return $user->can(P::WORKS_CREATE);
    }

    /**
     * Azioni della bacheca operatore (presa in carico, consegna, sospensione).
     */
    public function operate(User $user, Work $work): bool
    {
        return ($user->isWorksOperator() && $user->isAssignedToWork($work))
            || $user->canManageWorks();
    }

    public function update(User $user, Work $work): bool
    {
        return ($user->hasAllAbilities(P::WORKS_EDIT, P::WORKS_VIEW_ALL))
            || ($user->isWorksOperator() && $user->isAssignedToWork($work));
    }

    public function duplicate(User $user, Work $work): bool
    {
        return $user->can(P::WORKS_CREATE) && $user->canViewAllWorks();
    }

    public function complete(User $user, Work $work): bool
    {
        return $user->canManageWorks();
    }

    public function delete(User $user, Work $work): bool
    {
        return $user->hasAllAbilities(P::WORKS_DELETE, P::WORKS_VIEW_ALL);
    }
}
