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
        Schema::table('works', function (Blueprint $table) {
            $table->index('status');
            $table->index('ntw_scope');
            $table->index('phase');
            $table->index('wo_number');
            $table->index('unica_number');
            $table->index('created_at');
            $table->index('completion_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['ntw_scope']);
            $table->dropIndex(['phase']);
            $table->dropIndex(['wo_number']);
            $table->dropIndex(['unica_number']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['completion_date']);
        });
    }
};
