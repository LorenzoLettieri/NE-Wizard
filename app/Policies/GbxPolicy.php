<?php

namespace App\Policies;

use App\Models\Gbx;
use App\Models\User;

class GbxPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

    public function view(User $user, Gbx $gbx): bool
    {
        return $this->canWorkOn($user, $gbx);
    }

    public function update(User $user, Gbx $gbx): bool
    {
        return $this->canWorkOn($user, $gbx);
    }

    public function delete(User $user, Gbx $gbx): bool
    {
        return false;
    }

    private function canWorkOn(User $user, Gbx $gbx): bool
    {
        if ($user->hasRole('GBX Supervisor')) {
            return true;
        }

        return $user->hasRole('GBX') && $user->belongsToGbxCompany($gbx);
    }
}
