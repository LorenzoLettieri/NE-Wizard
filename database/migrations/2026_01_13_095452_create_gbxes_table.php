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
        Schema::create('gbxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('network')->nullable();
            $table->string('SDF')->nullable();
            $table->foreignId('central_id')->nullable()->constrained('centrals')->nullOnDelete();
            $table->string('comune')->nullable();
            $table->string('client')->nullable();
            $table->string('coordinates')->nullable();
            $table->date('appointment_date')->nullable();
            $table->date('inspection_date')->nullable();
            $table->date('verbal_date')->nullable();
            $table->boolean('is_adeguate')->nullable();
            $table->date('obligation_date')->nullable();
            $table->date('release_date')->nullable();
            $table->date('project_date')->nullable();
            $table->date('speedark_date')->nullable();
            $table->boolean('permissions')->nullable();
            $table->date('permission_request_date')->nullable();
            $table->date('permission_obtain_date')->nullable();
            $table->boolean('CO_advancement')->nullable();
            $table->date('cart_update_date')->nullable();
            $table->decimal('value', 12, 2)->nullable();
            $table->decimal('company_paid', 12, 2)->nullable();
            $table->decimal('bezzi_paid', 12, 2)->nullable();
            $table->decimal('project_paid', 12, 2)->nullable();
            $table->decimal('dl_paid', 12, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gbxes');
    }
};
