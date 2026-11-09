<?php

namespace App\Policies;

use App\Models\Gbx;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog as P;

class GbxPolicy
{
    public function view(User $user, Gbx $gbx): bool
    {
        return $user->canViewAllGbx()
            || ($user->can(P::GBX_ACCESS) && $user->belongsToGbxCompany($gbx));
    }

    public function create(User $user): bool
    {
        return $user->hasAllAbilities(P::GBX_ACCESS, P::GBX_CREATE);
    }

    public function update(User $user, Gbx $gbx): bool
    {
        return $user->can(P::GBX_EDIT) && $this->view($user, $gbx);
    }

    public function delete(User $user, Gbx $gbx): bool
    {
        return $user->can(P::GBX_DELETE) && $this->view($user, $gbx);
    }
}
