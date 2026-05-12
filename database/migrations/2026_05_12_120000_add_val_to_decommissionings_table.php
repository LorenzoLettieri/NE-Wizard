<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decommissionings', function (Blueprint $table) {
            $table->boolean('val')->default(false)->after('pagata_ne');
        });
    }

    public function down(): void
    {
        Schema::table('decommissionings', function (Blueprint $table) {
            $table->dropColumn('val');
        });
    }
};
