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
            $table->timestamp('acception_date')->nullable()->after('status');
            $table->timestamp('delivery_date')->nullable()->after('acception_date');
            $table->timestamp('completion_date')->nullable()->after('delivery_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('permessi_ente', function (Blueprint $table) {
            $table->dropColumn(['acception_date', 'delivery_date', 'completion_date']);
        });
    }
};
