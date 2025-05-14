<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::create(["name"=> "get works"]);

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'supervisor'])->givePermissionTo('get works');
        Role::create(['name'=> 'operator'])->givePermissionTo('get works');
    }
}
