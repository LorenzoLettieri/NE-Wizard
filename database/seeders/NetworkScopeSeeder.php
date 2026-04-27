<?php

namespace Database\Seeders;

use App\Models\NetworkScope;
use Illuminate\Database\Seeder;

class NetworkScopeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (NetworkScope::DEFAULT_NAMES as $name) {
            NetworkScope::updateOrCreate(['name' => $name]);
        }
    }
}
