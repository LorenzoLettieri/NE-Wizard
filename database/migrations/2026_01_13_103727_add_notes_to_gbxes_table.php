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
        Schema::table('gbxes', function (Blueprint $table) {
            $table->text('inspection_notes')->nullable();
            $table->text('permission_notes')->nullable();
            $table->text('project_notes')->nullable();
            $table->text('client_notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gbxes', function (Blueprint $table) {
            $table->dropColumn(['inspection_notes', 'permission_notes', 'project_notes', 'client_notes']);
        });
    }
};
