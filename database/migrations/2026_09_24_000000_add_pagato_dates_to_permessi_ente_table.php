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
        Schema::table('permessi_ente', function (Blueprint $table) {
            $table->date('pagato_dl')->nullable()->after('mese_saldo');
            $table->date('pagato_ne')->nullable()->after('pagato_dl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permessi_ente', function (Blueprint $table) {
            $table->dropColumn(['pagato_dl', 'pagato_ne']);
        });
    }
};
