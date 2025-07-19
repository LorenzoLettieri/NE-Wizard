<?php

namespace Database\Seeders;

use App\Models\Work;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FixNamesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Work::withTrashed()->where('phase','STEP 1')->update(['phase'=>'FASE 1']);

        Work::withTrashed()->where('phase','STEP 2')->update(['phase'=>'FASE 2']);

        Work::withTrashed()->where('phase','AGGIORN')->update(['phase'=>'AGGIORNAMENTO']);
    
        Work::withTrashed()->where('status','DA LAV')->update(['status'=>'Da Lavorare']);
        
        Work::withTrashed()->where('status','IN LAV')->update(['status'=>'In Lavorazione']);
        
        Work::withTrashed()->where('status','SOSP')->update(['status'=>'Sospeso']);
         
        Work::withTrashed()->where('status','OK')->update(['status'=>'Fine Lavori']);

        Work::withTrashed()->where('status','ATT FL')->update(['status'=>'Attesa Fine Lavori']);

    }
}
