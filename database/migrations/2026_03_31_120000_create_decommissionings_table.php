<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('decommissionings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('central_id')->nullable()->index()->constrained('centrals')->nullOnDelete();
            $table->foreignId('comune_id')->nullable()->index()->constrained('comuni')->nullOnDelete();
            $table->foreignId('regione_id')->nullable()->index()->constrained('regioni')->nullOnDelete();
            $table->foreignId('progettista_id')->nullable()->index()->constrained('users')->nullOnDelete();
            $table->string('permesso_ente')->nullable()->index();
            $table->date('fl_permesso')->nullable()->index();
            $table->text('note_permesso')->nullable();
            $table->string('status')->default('Da Lavorare')->index();
            $table->date('data_il')->nullable()->index();
            $table->date('data_fl')->nullable()->index();
            $table->unsignedInteger('qty_1')->nullable();
            $table->unsignedInteger('qty_2')->nullable();
            $table->unsignedInteger('qty_3')->nullable();
            $table->unsignedInteger('qty_4')->nullable();
            $table->unsignedInteger('qty_5')->nullable();
            $table->unsignedInteger('qty_6')->nullable();
            $table->decimal('prog_amount_1', 12, 2)->nullable();
            $table->decimal('prog_amount_2', 12, 2)->nullable();
            $table->decimal('prog_amount_3', 12, 2)->nullable();
            $table->decimal('prog_amount_4', 12, 2)->nullable();
            $table->decimal('prog_amount_5', 12, 2)->nullable();
            $table->decimal('prog_amount_6', 12, 2)->nullable();
            $table->decimal('ne_amount_1', 12, 2)->nullable();
            $table->decimal('ne_amount_2', 12, 2)->nullable();
            $table->decimal('ne_amount_3', 12, 2)->nullable();
            $table->decimal('ne_amount_4', 12, 2)->nullable();
            $table->decimal('ne_amount_5', 12, 2)->nullable();
            $table->decimal('ne_amount_6', 12, 2)->nullable();
            $table->decimal('tot_prog', 12, 2)->nullable();
            $table->decimal('tot_ne', 12, 2)->nullable();
            $table->decimal('agio', 12, 2)->nullable();
            $table->boolean('pagata_prog')->default(false);
            $table->boolean('pagata_ne')->default(false);
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decommissionings');
    }
};
