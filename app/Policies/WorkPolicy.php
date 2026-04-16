<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Work;

class WorkPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->canManageAllWorks() ? true : null;
    }

    public function view(User $user, Work $work): bool
    {
        return $user->isAssignedToWork($work);
    }

    public function operate(User $user, Work $work): bool
    {
        return $user->isAssignedToWork($work);
    }

    public function update(User $user, Work $work): bool
    {
        return $user->isAssignedToWork($work);
    }

    public function duplicate(User $user, Work $work): bool
    {
        return false;
    }

    public function complete(User $user, Work $work): bool
    {
        return false;
    }
}
