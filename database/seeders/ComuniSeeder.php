<?php

namespace Database\Seeders;

use App\Models\Comune;
use App\Models\Regione;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ComuniSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $json = json_decode(Storage::disk('public')->get('data/elenco_comuni.json'));

        foreach ($json as $entry) {
            Comune::create([
                'comune_progressive' => $entry->comune_progressive,
                'code' => $entry->code,
                'name' => $entry->name,
                'location' => $entry->location,
                'regione_id' => Regione::where('nome', $entry->region)->first()?->id,
                'sovracomune' => $entry->sovracomune,
                'catasto_code' => $entry->catasto_code,
            ]);
        }
    }
}
