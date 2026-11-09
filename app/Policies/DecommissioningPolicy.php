<?php

namespace App\Policies;

use App\Models\Decommissioning;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog as P;

class DecommissioningPolicy
{
    public function view(User $user, Decommissioning $decommissioning): bool
    {
        return $user->canViewAllDecommissionings()
            || ($user->can(P::DECO_ACCESS) && $user->isAssignedToDecommissioning($decommissioning));
    }

    public function create(User $user): bool
    {
        return $user->hasAllAbilities(P::DECO_ACCESS, P::DECO_CREATE);
    }

    public function update(User $user, Decommissioning $decommissioning): bool
    {
        return $user->can(P::DECO_EDIT) && $this->view($user, $decommissioning);
    }

    public function delete(User $user, Decommissioning $decommissioning): bool
    {
        return $user->can(P::DECO_DELETE) && $this->view($user, $decommissioning);
    }
}
