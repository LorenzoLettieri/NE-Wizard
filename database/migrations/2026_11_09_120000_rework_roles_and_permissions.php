<?php

use App\Models\User;
use App\Support\Authorization\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Passa dai controlli per nome di ruolo ai permessi granulari del catalogo.
 *
 * I ruoli esistenti ricevono i permessi che riproducono il comportamento precedente, e chi aveva
 * un vecchio permesso ("get works", ...) direttamente o tramite un ruolo riceve il suo equivalente.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (PermissionCatalog::all() as $name) {
                Permission::findOrCreate($name, 'web');
            }

            foreach (PermissionCatalog::legacyPermissions() as $legacyName => $replacements) {
                $legacy = Permission::where('name', $legacyName)->where('guard_name', 'web')->first();

                if ($legacy === null) {
                    continue;
                }

                foreach ($legacy->roles as $role) {
                    $role->givePermissionTo($replacements);
                }

                foreach (User::permission($legacyName)->get() as $user) {
                    // Solo i permessi diretti: quelli dei ruoli sono già tradotti sopra
                    if ($user->getDirectPermissions()->contains('name', $legacyName)) {
                        $user->givePermissionTo($replacements);
                    }
                }
            }

            PermissionCatalog::grantDefaults();
            PermissionCatalog::sync();

            Permission::whereIn('name', array_keys(PermissionCatalog::legacyPermissions()))
                ->where('guard_name', 'web')
                ->get()
                ->each->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $legacy = [
                'get works' => PermissionCatalog::WORKS_OPERATE,
                'get permessi ente' => PermissionCatalog::PERMESSI_OPERATE,
                'get gbx' => PermissionCatalog::GBX_ACCESS,
                'get decommissioning' => PermissionCatalog::DECO_ACCESS,
            ];

            foreach ($legacy as $legacyName => $source) {
                Permission::findOrCreate($legacyName, 'web');
                $current = Permission::where('name', $source)->where('guard_name', 'web')->first();

                foreach ($current?->roles ?? [] as $role) {
                    $role->givePermissionTo($legacyName);
                }

                foreach ($current?->users ?? [] as $user) {
                    $user->givePermissionTo($legacyName);
                }
            }

            // Il vecchio seeder dava all'admin solo GBX e Decommissioning
            Role::where('name', PermissionCatalog::SUPER_ADMIN_ROLE)->first()
                ?->syncPermissions(['get gbx', 'get decommissioning']);

            Permission::whereIn('name', PermissionCatalog::all())->where('guard_name', 'web')->get()->each->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
