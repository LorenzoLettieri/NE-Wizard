<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_decommissioning_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('item_index');
            $table->decimal('prog_price', 10, 2)->nullable();
            $table->decimal('ne_price', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'item_index']);
        });

        $defaultRates = [
            1 => ['prog_price' => 120, 'ne_price' => 185],
            2 => ['prog_price' => 120, 'ne_price' => 185],
            3 => ['prog_price' => 30, 'ne_price' => 49],
            4 => ['prog_price' => 120, 'ne_price' => 185],
            5 => ['prog_price' => 12, 'ne_price' => 12],
            6 => ['prog_price' => 12, 'ne_price' => 12],
        ];

        $now = now();
        $rows = [];

        foreach (DB::table('companies')->pluck('id') as $companyId) {
            foreach ($defaultRates as $itemIndex => $rate) {
                $rows[] = [
                    'company_id' => $companyId,
                    'item_index' => $itemIndex,
                    'prog_price' => $rate['prog_price'],
                    'ne_price' => $rate['ne_price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($rows !== []) {
            DB::table('company_decommissioning_rates')->insert($rows);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('company_decommissioning_rates');
    }
};
