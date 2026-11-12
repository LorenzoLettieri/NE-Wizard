<?php

namespace App\Support\Authorization;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Unico elenco dei permessi dell'applicazione.
 *
 * Ogni modulo ha tre gruppi di permessi:
 * - visibilità (quali record vede: tutti o solo i propri);
 * - azioni (cosa può fare);
 * - contabilità (vedere/modificare i dati economici).
 *
 * Il ruolo SUPER_ADMIN_ROLE riceve sempre tutti i permessi tranne quelli "personali", che descrivono
 * ciò che una persona fa (es. timbrare, essere assegnabile come operatore) e non un'autorità.
 * Gestione utenti, gestione ruoli e Adminer restano riservati al super admin per ruolo, non per permesso,
 * così nessun ruolo personalizzato può assegnarsi altri permessi.
 */
final class PermissionCatalog
{
    public const SUPER_ADMIN_ROLE = 'admin';

    // Lavorazioni
    public const WORKS_VIEW_ALL = 'works.view.all';
    public const WORKS_CREATE = 'works.create';
    public const WORKS_EDIT = 'works.edit';
    public const WORKS_MANAGE = 'works.manage';
    public const WORKS_DELETE = 'works.delete';
    public const WORKS_EXPORT = 'works.export';
    public const WORKS_FINANCE_VIEW = 'works.finance.view';
    public const WORKS_OPERATE = 'works.operate';

    // Permessi Ente
    public const PERMESSI_ACCESS = 'permessi.access';
    public const PERMESSI_VIEW_ALL = 'permessi.view.all';
    public const PERMESSI_CREATE = 'permessi.create';
    public const PERMESSI_EDIT = 'permessi.edit';
    public const PERMESSI_MANAGE = 'permessi.manage';
    public const PERMESSI_DELETE = 'permessi.delete';
    public const PERMESSI_EXPORT = 'permessi.export';
    public const PERMESSI_FINANCE_VIEW = 'permessi.finance.view';
    public const PERMESSI_FINANCE_EDIT = 'permessi.finance.edit';
    public const PERMESSI_OPERATE = 'permessi.operate';

    // GBX
    public const GBX_ACCESS = 'gbx.access';
    public const GBX_VIEW_ALL = 'gbx.view.all';
    public const GBX_CREATE = 'gbx.create';
    public const GBX_EDIT = 'gbx.edit';
    public const GBX_DELETE = 'gbx.delete';
    public const GBX_EXPORT = 'gbx.export';
    public const GBX_FINANCE_VIEW = 'gbx.finance.view';
    public const GBX_FINANCE_EDIT = 'gbx.finance.edit';

    // Decommissioning
    public const DECO_ACCESS = 'deco.access';
    public const DECO_VIEW_ALL = 'deco.view.all';
    public const DECO_CREATE = 'deco.create';
    public const DECO_EDIT = 'deco.edit';
    public const DECO_DELETE = 'deco.delete';
    public const DECO_EXPORT = 'deco.export';
    public const DECO_IMPORT = 'deco.import';
    public const DECO_FINANCE_VIEW = 'deco.finance.view';
    public const DECO_FINANCE_EDIT = 'deco.finance.edit';
    public const DECO_REPORT = 'deco.report';
    public const DECO_ASSIGNABLE = 'deco.assignable';

    // Report operatori
    public const REPORTS_OPERATORS = 'reports.operators';
    public const REPORTS_OPERATORS_ACTIVITY = 'reports.operators.activity';
    public const OPERATORS_WORKSPACE = 'operators.workspace';

    // Presenze
    public const TIMESHEETS_CLOCK = 'timesheets.clock';
    public const TIMESHEETS_TRACKED = 'timesheets.tracked';
    public const TIMESHEETS_MANAGE = 'timesheets.manage';

    // Impostazioni
    public const SETTINGS_BASE_TABLES = 'settings.base-tables';
    public const SETTINGS_RATES = 'settings.rates';

    /**
     * Permessi raggruppati per modulo, con l'etichetta da mostrare nella gestione ruoli.
     *
     * @return array<string, array<string, string>>
     */
    public static function groups(): array
    {
        return [
            'Lavorazioni' => [
                self::WORKS_VIEW_ALL => 'Tabella Lavorazioni: vede tutte le lavorazioni',
                self::WORKS_CREATE => 'Crea e duplica lavorazioni',
                self::WORKS_EDIT => 'Modifica tutte le lavorazioni',
                self::WORKS_MANAGE => 'Gestione: stato, data prevista, sospensioni, operatori assegnati, Fine Lavori',
                self::WORKS_DELETE => 'Elimina lavorazioni',
                self::WORKS_EXPORT => 'Export Excel (senza importi se manca la contabilità)',
                self::WORKS_FINANCE_VIEW => 'Contabilità: importi e tariffe, anche nel Report Operatori',
                self::WORKS_OPERATE => 'Operatore: bacheca, riceve lavorazioni assegnate e le lavora',
            ],
            'Permessi Ente' => [
                self::PERMESSI_ACCESS => 'Accesso alla sezione',
                self::PERMESSI_VIEW_ALL => 'Vede tutti i permessi (non solo quelli assegnati)',
                self::PERMESSI_CREATE => 'Crea permessi',
                self::PERMESSI_EDIT => 'Modifica permessi visibili',
                self::PERMESSI_MANAGE => 'Gestione: stato, operatori assegnati, Fine Lavori, duplica, date operatore',
                self::PERMESSI_DELETE => 'Elimina permessi',
                self::PERMESSI_EXPORT => 'Export Excel (senza importi se manca la contabilità)',
                self::PERMESSI_FINANCE_VIEW => 'Contabilità: vede Al DL, A NE, Delta, Pagato, Mese Saldo, VDC',
                self::PERMESSI_FINANCE_EDIT => 'Contabilità: modifica Al DL, A NE, Delta, Pagato, Mese Saldo, VDC',
                self::PERMESSI_OPERATE => 'Operatore: assegnabile, prende in carico e consegna',
            ],
            'GBX' => [
                self::GBX_ACCESS => 'Accesso alla sezione',
                self::GBX_VIEW_ALL => 'Vede tutti i GBX (non solo quelli della propria impresa)',
                self::GBX_CREATE => 'Crea GBX',
                self::GBX_EDIT => 'Modifica GBX visibili',
                self::GBX_DELETE => 'Elimina GBX',
                self::GBX_EXPORT => 'Export Excel (senza importi se manca la contabilità)',
                self::GBX_FINANCE_VIEW => 'Contabilità: vede valore e pagamenti',
                self::GBX_FINANCE_EDIT => 'Contabilità: modifica valore e pagamenti',
            ],
            'Decommissioning' => [
                self::DECO_ACCESS => 'Accesso alla sezione',
                self::DECO_VIEW_ALL => 'Vede tutti i decommissioning (non solo quelli di cui è progettista)',
                self::DECO_CREATE => 'Crea decommissioning',
                self::DECO_EDIT => 'Modifica decommissioning visibili',
                self::DECO_DELETE => 'Elimina decommissioning',
                self::DECO_EXPORT => 'Export Excel (senza importi se manca la contabilità)',
                self::DECO_IMPORT => 'Import Excel: modifica massiva dei decommissioning visibili (importi solo con la modifica della contabilità)',
                self::DECO_FINANCE_VIEW => 'Contabilità: vede importi, pagamenti e Val',
                self::DECO_FINANCE_EDIT => 'Contabilità: modifica importi, pagamenti e Val',
                self::DECO_REPORT => 'Report Deco (contiene importi: serve anche la visione della contabilità)',
                self::DECO_ASSIGNABLE => 'Progettista: può essere assegnato ai decommissioning',
            ],
            'Report operatori' => [
                self::REPORTS_OPERATORS => 'Report Operatori',
                self::REPORTS_OPERATORS_ACTIVITY => 'Timeline attività operatori (presenze, pause, lavorazioni)',
                self::OPERATORS_WORKSPACE => 'Vede la bacheca di un operatore in sola lettura',
            ],
            'Presenze' => [
                self::TIMESHEETS_CLOCK => 'Timbra la propria presenza',
                self::TIMESHEETS_TRACKED => 'Compare nella Gestione Presenze e nel suo export',
                self::TIMESHEETS_MANAGE => 'Gestione Presenze ed export',
            ],
            'Impostazioni' => [
                self::SETTINGS_BASE_TABLES => 'Tabelle di base: centrali, comuni, regioni, imprese, fasi, ambiti',
                self::SETTINGS_RATES => 'Matrici prezzi e target di produzione (dati economici)',
            ],
        ];
    }

    /**
     * Permessi che descrivono la persona e non vanno dati in automatico al super admin.
     *
     * @return list<string>
     */
    public static function personal(): array
    {
        return [
            self::WORKS_OPERATE,
            self::PERMESSI_OPERATE,
            self::DECO_ASSIGNABLE,
            self::TIMESHEETS_CLOCK,
            self::TIMESHEETS_TRACKED,
        ];
    }

    /**
     * Coppie "modifica => visione": chi modifica un dato deve anche poterlo vedere.
     *
     * @return array<string, string>
     */
    public static function implied(): array
    {
        return [
            self::WORKS_EDIT => self::WORKS_VIEW_ALL,
            self::WORKS_MANAGE => self::WORKS_VIEW_ALL,
            self::WORKS_DELETE => self::WORKS_VIEW_ALL,
            self::WORKS_EXPORT => self::WORKS_VIEW_ALL,
            self::PERMESSI_VIEW_ALL => self::PERMESSI_ACCESS,
            self::PERMESSI_CREATE => self::PERMESSI_ACCESS,
            self::PERMESSI_EDIT => self::PERMESSI_ACCESS,
            self::PERMESSI_MANAGE => self::PERMESSI_ACCESS,
            self::PERMESSI_DELETE => self::PERMESSI_ACCESS,
            self::PERMESSI_EXPORT => self::PERMESSI_ACCESS,
            self::PERMESSI_FINANCE_VIEW => self::PERMESSI_ACCESS,
            self::PERMESSI_FINANCE_EDIT => self::PERMESSI_FINANCE_VIEW,
            self::PERMESSI_OPERATE => self::PERMESSI_ACCESS,
            self::GBX_VIEW_ALL => self::GBX_ACCESS,
            self::GBX_CREATE => self::GBX_ACCESS,
            self::GBX_EDIT => self::GBX_ACCESS,
            self::GBX_DELETE => self::GBX_ACCESS,
            self::GBX_EXPORT => self::GBX_ACCESS,
            self::GBX_FINANCE_VIEW => self::GBX_ACCESS,
            self::GBX_FINANCE_EDIT => self::GBX_FINANCE_VIEW,
            self::DECO_VIEW_ALL => self::DECO_ACCESS,
            self::DECO_CREATE => self::DECO_ACCESS,
            self::DECO_EDIT => self::DECO_ACCESS,
            self::DECO_DELETE => self::DECO_ACCESS,
            self::DECO_EXPORT => self::DECO_ACCESS,
            self::DECO_IMPORT => self::DECO_EDIT,
            self::DECO_FINANCE_VIEW => self::DECO_ACCESS,
            self::DECO_FINANCE_EDIT => self::DECO_FINANCE_VIEW,
            self::DECO_REPORT => self::DECO_FINANCE_VIEW,
            self::DECO_ASSIGNABLE => self::DECO_ACCESS,
            self::REPORTS_OPERATORS_ACTIVITY => self::REPORTS_OPERATORS,
        ];
    }

    /**
     * Completa un insieme di permessi con quelli che implicano (es. finance.edit => finance.view => access).
     *
     * @param  list<string>  $permissions
     * @return list<string>
     */
    public static function withImplied(array $permissions): array
    {
        $implied = self::implied();
        $result = array_values(array_unique(array_intersect($permissions, self::all())));

        for ($i = 0; $i < count($result); $i++) {
            $parent = $implied[$result[$i]] ?? null;

            if ($parent !== null && ! in_array($parent, $result, true)) {
                $result[] = $parent;
            }
        }

        return array_values(array_intersect(self::all(), $result));
    }

    /**
     * Permessi di partenza dei ruoli predefiniti: riproducono il comportamento precedente al rework.
     *
     * @return array<string, list<string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            'supervisor' => [
                self::WORKS_VIEW_ALL,
                self::WORKS_CREATE,
                self::WORKS_EDIT,
                self::WORKS_MANAGE,
                self::WORKS_DELETE,
                self::WORKS_EXPORT,
                self::WORKS_OPERATE,
                self::REPORTS_OPERATORS,
                self::TIMESHEETS_CLOCK,
            ],
            'operator' => [
                self::WORKS_OPERATE,
                self::TIMESHEETS_CLOCK,
                self::TIMESHEETS_TRACKED,
            ],
            'freelance operator' => [
                self::WORKS_OPERATE,
            ],
            'permessi ente' => [
                self::PERMESSI_ACCESS,
                self::PERMESSI_CREATE,
                self::PERMESSI_EDIT,
                self::PERMESSI_OPERATE,
            ],
            'GBX' => [
                self::GBX_ACCESS,
                self::GBX_CREATE,
                self::GBX_EDIT,
            ],
            'GBX Supervisor' => [
                self::GBX_ACCESS,
                self::GBX_VIEW_ALL,
                self::GBX_CREATE,
                self::GBX_EDIT,
            ],
            'Deco' => [
                self::DECO_ACCESS,
                self::DECO_CREATE,
                self::DECO_EDIT,
                self::DECO_ASSIGNABLE,
            ],
        ];
    }

    /**
     * Vecchi permessi e loro equivalente, per chi li aveva assegnati direttamente o su ruoli personalizzati.
     *
     * @return array<string, list<string>>
     */
    public static function legacyPermissions(): array
    {
        return [
            'get works' => [self::WORKS_OPERATE],
            'get permessi ente' => [self::PERMESSI_ACCESS, self::PERMESSI_CREATE, self::PERMESSI_EDIT, self::PERMESSI_OPERATE],
            'get gbx' => [self::GBX_ACCESS, self::GBX_CREATE, self::GBX_EDIT],
            'get decommissioning' => [self::DECO_ACCESS, self::DECO_CREATE, self::DECO_EDIT, self::DECO_ASSIGNABLE],
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_merge(...array_map('array_keys', array_values(self::groups())));
    }

    /**
     * @return list<string>
     */
    public static function superAdminPermissions(): array
    {
        return array_values(array_diff(self::all(), self::personal()));
    }

    public static function label(string $permission): ?string
    {
        foreach (self::groups() as $permissions) {
            if (isset($permissions[$permission])) {
                return $permissions[$permission];
            }
        }

        return null;
    }

    /**
     * Crea i permessi mancanti e dà al super admin tutti quelli non personali. Idempotente.
     */
    public static function sync(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::all() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        Role::findOrCreate(self::SUPER_ADMIN_ROLE, 'web')->syncPermissions(self::superAdminPermissions());

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Aggiunge ai ruoli predefiniti i loro permessi di partenza, senza togliere quelli già presenti.
     */
    public static function grantDefaults(bool $createRoles = false): void
    {
        foreach (self::defaultRolePermissions() as $roleName => $permissions) {
            $role = $createRoles
                ? Role::findOrCreate($roleName, 'web')
                : Role::where('name', $roleName)->where('guard_name', 'web')->first();

            $role?->givePermissionTo($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
