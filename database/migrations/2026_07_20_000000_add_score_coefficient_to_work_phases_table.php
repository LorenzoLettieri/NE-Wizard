<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_phases', function (Blueprint $table) {
            $table->decimal('score_coefficient', 8, 2)->default(0)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('work_phases', function (Blueprint $table) {
            $table->dropColumn('score_coefficient');
        });
    }
};
