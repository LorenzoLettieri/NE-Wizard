<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $this->call(RoleSeeder::class);
        $this->call(CompanySeeder::class);
        $this->call(CentralSeeder::class);

        $user = User::factory()->create([
            'name' => 'user',
            'email' => 'user@test.com',
            'password' => '12345678'
        ]);
        $user->assignRole('operator');
        

        $admin = User::factory()->create([
            'name' => 'admin',
            'email' => 'admin@test.com',
            'password' => '12345678'
        ]);
        $admin->assignRole('admin');

        $supervisor = User::factory()->create([
            'name' => 'supervisor',
            'email' => 'supervisor@test.com',
            'password' => '12345678'
        ]);
        $supervisor->assignRole('supervisor');

    }
}
