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
        Schema::create('comuni', function (Blueprint $table) {
            $table->id();
            $table->string('comune_progressive');
            $table->string('code');
            $table->string('name');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('regione_id')->references('id')->on('regioni')->onDelete('set null')->nullable();
            $table->string('sovracomune')->nullable();
            $table->string('catasto_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comuni');
    }
};
