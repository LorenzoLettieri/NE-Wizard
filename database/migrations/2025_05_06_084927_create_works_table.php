<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->string('status')->nullable();
            $table->string('network')->nullable();
            $table->string('ao_cno')->nullable();
            $table->text('description')->nullable();
            $table->string('ntw_scope')->nullable();
            $table->string('type')->nullable();
            $table->string('phase')->nullable();
            $table->string('company_assistant')->nullable();
            $table->date('completion_date')->nullable();
            $table->dateTime('acception_date')->nullable();
            $table->dateTime('delivery_date')->nullable();
            $table->integer('nroe')->nullable();
            $table->string('wo_number')->nullable();
            $table->string('unica_number')->nullable();
            $table->longText('suspension_history')->nullable();
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
