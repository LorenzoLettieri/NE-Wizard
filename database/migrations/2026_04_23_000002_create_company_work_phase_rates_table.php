<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_work_phase_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_phase_id')->constrained()->cascadeOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->unique(['company_id', 'work_phase_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_work_phase_rates');
    }
};
