<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->foreignId('network_scope_id')
                ->nullable()
                ->after('ntw_scope')
                ->constrained('network_scopes')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropForeign(['network_scope_id']);
            $table->dropColumn('network_scope_id');
        });
    }
};
