<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
