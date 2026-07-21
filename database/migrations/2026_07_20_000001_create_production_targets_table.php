<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_targets', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_amount_target', 10, 2)->default(3500);
            $table->decimal('daily_score_target', 10, 2)->default(0);
            $table->timestamps();
        });

        DB::table('production_targets')->insert([
            'monthly_amount_target' => 3500,
            'daily_score_target' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('production_targets');
    }
};
