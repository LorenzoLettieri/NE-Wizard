<?php

namespace Database\Seeders;

use App\Models\WorkPhase;
use Illuminate\Database\Seeder;

class WorkPhaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (WorkPhase::DEFAULT_NAMES as $name) {
            WorkPhase::updateOrCreate(['name' => $name]);
        }
    }
}
