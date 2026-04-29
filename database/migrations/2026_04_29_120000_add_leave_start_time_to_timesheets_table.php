<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('timesheets', 'leave_start_time')) {
            return;
        }

        Schema::table('timesheets', function (Blueprint $table) {
            $table->timestamp('leave_start_time')->nullable()->after('break_end');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('timesheets', 'leave_start_time')) {
            return;
        }

        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropColumn('leave_start_time');
        });
    }
};
