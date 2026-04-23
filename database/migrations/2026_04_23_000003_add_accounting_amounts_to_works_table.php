<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->decimal('unit_rate', 10, 2)->nullable()->after('nroe');
            $table->decimal('accounting_amount', 12, 2)->nullable()->after('unit_rate');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table) {
            $table->dropColumn(['unit_rate', 'accounting_amount']);
        });
    }
};
