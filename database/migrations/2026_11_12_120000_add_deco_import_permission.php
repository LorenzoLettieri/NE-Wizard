<?php

use App\Support\Authorization\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crea il permesso deco.import (modifica massiva da Excel) e lo dà al super admin.
 * Gli altri ruoli lo ricevono dalla gestione ruoli.
 */
return new class extends Migration
{
    public function up(): void
    {
        PermissionCatalog::sync();
    }

    public function down(): void
    {
        Permission::where('name', PermissionCatalog::DECO_IMPORT)->where('guard_name', 'web')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
