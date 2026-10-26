<?php

namespace App\Policies;

use App\Models\Decommissioning;
use App\Models\User;

class DecommissioningPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function view(User $user, Decommissioning $decommissioning): bool
    {
        return $user->isAssignedToDecommissioning($decommissioning);
    }

    public function update(User $user, Decommissioning $decommissioning): bool
    {
        return $user->isAssignedToDecommissioning($decommissioning);
    }

    public function delete(User $user, Decommissioning $decommissioning): bool
    {
        return false;
    }
}
