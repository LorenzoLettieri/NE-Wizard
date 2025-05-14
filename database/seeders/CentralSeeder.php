<?php

namespace Database\Seeders;

use App\Models\Central;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CentralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = json_decode(Storage::disk('public')->get('data/central_region.json'));

        foreach ($json as $entry) {
            Central::create([
                'central'=> $entry->central,
                'region' => $entry->region,
            ]);
        }
    }
}
