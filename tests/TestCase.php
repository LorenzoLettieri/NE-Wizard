<?php

namespace Tests;

use App\Models\User;
use App\Support\Authorization\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    /**
     * Utente super admin, con ruoli e permessi del catalogo già creati.
     */
    protected function superAdmin(): User
    {
        if (! Role::where('name', PermissionCatalog::SUPER_ADMIN_ROLE)->exists()) {
            $this->seed(RoleSeeder::class);
        }

        $user = User::factory()->create();
        $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE);

        return $user;
    }
}
