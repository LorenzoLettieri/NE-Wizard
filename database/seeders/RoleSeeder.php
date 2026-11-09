<?php

namespace Database\Seeders;

use App\Support\Authorization\PermissionCatalog;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PermissionCatalog::sync();
        PermissionCatalog::grantDefaults(createRoles: true);
    }
}
