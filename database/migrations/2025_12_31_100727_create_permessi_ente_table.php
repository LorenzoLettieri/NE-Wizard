<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permessi_ente', function (Blueprint $table) {
            $table->id();

            $table->string('status')->default('Da Lavorare')->index();

            // Campi principali
            $table->unsignedBigInteger('network')->nullable();
            $table->date('consegna')->nullable();
            $table->string('progetto')->nullable();
            $table->foreignId('regione_id')->nullable()->index()->constrained('regioni')->nullOnDelete();
            $table->foreignId('comune_id')->nullable()->index()->constrained('comuni')->nullOnDelete();
            $table->foreignId('central_id')->nullable()->index()->constrained('centrals')->nullOnDelete();
            $table->string('via')->nullable();
            $table->text('descrizione')->nullable();

            // Campi booleani
            $table->boolean('ap_chiusini')->default(false);
            $table->integer('num_chiusini')->nullable();
            $table->boolean('scavo_fino_100m')->default(false);
            $table->integer('quote_aggiuntive')->nullable();
            $table->boolean('urgente')->default(false);
            $table->boolean('ordinaria')->default(false);
            $table->boolean('fine_lavori')->default(false);
            $table->date('data_fl')->nullable()->index();
            $table->boolean('ra')->default(false);
            $table->date('data_ra')->nullable();
            $table->date('evaso_dal_dl')->nullable();

            // Campi economici
            $table->date('mese_saldo')->nullable(); // sarà scelto con time picker
            $table->decimal('al_dl', 10, 2)->nullable();
            $table->decimal('a_ne', 10, 2)->nullable();
            $table->decimal('delta', 10, 2)->nullable();

            // VDC
            $table->integer('vdc1')->nullable();
            $table->integer('vdc2')->nullable();
            $table->integer('vdc3')->nullable();
            $table->integer('vdc4')->nullable();

            $table->timestamps(); // contiene created_at = data di apertura record
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permessi_ente');
    }
};
