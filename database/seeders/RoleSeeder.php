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

        Permission::firstOrCreate(["name" => "get works"]);
        Permission::firstOrCreate(["name" => "get permessi ente"]);
        Permission::firstOrCreate(["name" => "get gbx"]);

        Role::firstOrCreate(['name' => 'admin'])->givePermissionTo('get gbx');
        Role::firstOrCreate(['name' => 'supervisor'])->givePermissionTo('get works');
        Role::firstOrCreate(['name' => 'operator'])->givePermissionTo('get works');
        Role::firstOrCreate(['name' => 'permessi ente'])->givePermissionTo('get permessi ente');
        Role::firstOrCreate(['name' => 'GBX'])->givePermissionTo('get gbx');
    }
}
