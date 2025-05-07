<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            'INPOWER','MDS','SIAT','SIELTE','SIRE','SIRTI','SITE','SITTEL','TELEBIT','TSD','VALTELLINA',
        ];

        foreach ($companies as $company) {
            Company::create([
                'name'=> $company,
            ]);
        }
    }
}
