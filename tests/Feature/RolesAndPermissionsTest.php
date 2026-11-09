<?php

namespace Tests\Feature;

use App\Exports\GbxExport;
use App\Exports\PermessiEnteExport;
use App\Exports\WorksExport;
use App\Livewire\AdminBaseTables;
use App\Livewire\EditWork;
use App\Livewire\GbxEdit;
use App\Livewire\GbxForm;
use App\Livewire\OperatorStats;
use App\Livewire\OperatorTable;
use App\Livewire\PermessiEnteTable;
use App\Livewire\PermessoEnteForm;
use App\Livewire\RoleManager;
use App\Livewire\UserTable;
use App\Livewire\WorkForm;
use App\Models\Company;
use App\Models\Gbx;
use App\Models\PermessoEnte;
use App\Models\User;
use App\Models\Work;
use App\Support\Authorization\PermissionCatalog as P;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Rework ruoli e permessi: i ruoli esistenti mantengono gli accessi di prima, i ruoli personalizzati
 * ottengono solo ciò che il catalogo concede, e i dati economici non arrivano a chi non può vederli.
 */
#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class RolesAndPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    // ---------------------------------------------------------------------
    // Ruoli esistenti: stessi accessi di prima del rework
    // ---------------------------------------------------------------------

    private const ROUTES = [
        'works-table', 'addWork', 'operator-table', 'operator-timesheet',
        'gbxes-table', 'addGbx', 'permessi-ente.table', 'addPermesso',
        'decommissionings.table', 'addDecommissioning',
        'reports.operators', 'users-table', 'reports.decommissioning',
        'accounts-table', 'admin.timesheets', 'admin.base-tables', 'admin.roles',
    ];

    public static function roleAccessMatrix(): array
    {
        return [
            'admin' => ['admin', [
                'works-table', 'addWork', 'gbxes-table', 'addGbx', 'permessi-ente.table', 'addPermesso',
                'decommissionings.table', 'addDecommissioning', 'reports.operators', 'users-table',
                'reports.decommissioning', 'accounts-table', 'admin.timesheets', 'admin.base-tables', 'admin.roles',
            ]],
            'supervisor' => ['supervisor', [
                'works-table', 'addWork', 'operator-table', 'operator-timesheet', 'reports.operators', 'users-table',
            ]],
            'operator' => ['operator', ['operator-table', 'operator-timesheet']],
            'freelance operator' => ['freelance operator', ['operator-table']],
            'permessi ente' => ['permessi ente', ['permessi-ente.table', 'addPermesso']],
            'GBX' => ['GBX', ['gbxes-table', 'addGbx']],
            'GBX Supervisor' => ['GBX Supervisor', ['gbxes-table', 'addGbx']],
            'Deco' => ['Deco', ['decommissionings.table', 'addDecommissioning']],
        ];
    }

    #[DataProvider('roleAccessMatrix')]
    public function test_existing_roles_keep_the_same_page_access(string $role, array $allowed): void
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        foreach (self::ROUTES as $route) {
            $status = $this->actingAs($user)->get(route($route))->getStatusCode();

            if (in_array($route, $allowed, true)) {
                $this->assertSame(200, $status, "{$role} dovrebbe accedere a {$route}");
            } else {
                $this->assertSame(403, $status, "{$role} non dovrebbe accedere a {$route}");
            }
        }
    }

    public function test_guests_are_redirected_from_protected_pages(): void
    {
        foreach (self::ROUTES as $route) {
            $this->get(route($route))->assertRedirect('/');
        }
    }

    // ---------------------------------------------------------------------
    // Migrazione da produzione
    // ---------------------------------------------------------------------

    public function test_migration_translates_legacy_roles_and_direct_permissions(): void
    {
        $this->resetToLegacyPermissions();

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $directOperator = User::factory()->create();
        $directOperator->givePermissionTo('get works');
        Role::create(['name' => 'Ruolo personalizzato'])->givePermissionTo('get gbx');
        $custom = User::factory()->create();
        $custom->assignRole('Ruolo personalizzato');

        $migration = require database_path('migrations/2026_11_09_120000_rework_roles_and_permissions.php');
        $migration->up();
        $this->refreshUsers($supervisor, $directOperator, $custom);

        foreach (P::defaultRolePermissions()['supervisor'] as $permission) {
            $this->assertTrue($supervisor->can($permission), "supervisor: {$permission}");
        }
        $this->assertTrue($directOperator->can(P::WORKS_OPERATE));
        $this->assertTrue($custom->can(P::GBX_ACCESS));
        $this->assertFalse($custom->can(P::GBX_FINANCE_VIEW));
        $this->assertSame(0, Permission::whereIn('name', array_keys(P::legacyPermissions()))->count());

        $admin = Role::findByName('admin');
        $this->assertEqualsCanonicalizing(P::superAdminPermissions(), $admin->permissions->pluck('name')->all());

        // Idempotente: una seconda esecuzione non cambia nulla e non fallisce
        $migration->up();
        $this->assertSame(count(P::all()), Permission::count());

        $migration->down();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->assertTrue(User::find($directOperator->id)->can('get works'));
        $this->assertTrue(User::find($supervisor->id)->can('get works'));
        $this->assertSame(0, Permission::whereIn('name', P::all())->count());
    }

    public function test_super_admin_gets_every_permission_except_personal_ones(): void
    {
        $admin = $this->superAdmin();

        foreach (P::superAdminPermissions() as $permission) {
            $this->assertTrue($admin->can($permission), $permission);
        }

        foreach (P::personal() as $permission) {
            $this->assertFalse($admin->can($permission), $permission);
        }
    }

    // ---------------------------------------------------------------------
    // Lavorazioni
    // ---------------------------------------------------------------------

    public function test_works_viewer_without_finance_gets_no_accounting_in_table_and_export(): void
    {
        Excel::fake();
        $viewer = $this->userWithPermissions([P::WORKS_VIEW_ALL, P::WORKS_EXPORT]);

        $this->actingAs($viewer)->get(route('works-table'))->assertOk();
        $this->actingAs($viewer)->get(route('addWork'))->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('exports.works', ['date_field' => 'created_at', 'start' => '2026-01-01', 'end' => '2026-12-31']))
            ->assertOk();

        Excel::assertDownloaded('works_created_at_2026-01-01_2026-12-31.xlsx', function (WorksExport $export) {
            return ! in_array('Importo contabilizzato', $export->headings(), true)
                && ! in_array('Tariffa unitaria', $export->headings(), true);
        });
    }

    public function test_works_export_requires_its_permission(): void
    {
        $viewer = $this->userWithPermissions([P::WORKS_VIEW_ALL]);

        $this->actingAs($viewer)
            ->get(route('exports.works', ['date_field' => 'created_at', 'start' => '2026-01-01', 'end' => '2026-12-31']))
            ->assertForbidden();
    }

    public function test_creator_without_manage_cannot_set_status_or_operators(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $creator = $this->userWithPermissions([P::WORKS_CREATE]);

        Livewire::actingAs($creator)
            ->test(WorkForm::class)
            ->set('status', 'In Lavorazione')
            ->set('expected_delivery_date', '2026-12-01')
            ->set('operator_id', [$operator->id])
            ->set('notes', 'CREATA-SENZA-GESTIONE')
            ->call('store')
            ->assertHasNoErrors();

        $work = Work::where('notes', 'CREATA-SENZA-GESTIONE')->firstOrFail();
        $this->assertSame('Da Lavorare', $work->status);
        $this->assertNull($work->expected_delivery_date);
        $this->assertSame(0, $work->users()->count());
    }

    public function test_operator_cannot_open_or_edit_works_not_assigned(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $work = Work::create(['status' => 'Da Lavorare']);

        Livewire::actingAs($operator)->test(EditWork::class)->call('editWork', $work->id)->assertForbidden();
        $this->actingAs($operator)->get(route('editWork', $work->id))->assertForbidden();
    }

    public function test_operator_board_cannot_be_redirected_to_another_operator(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $other = User::factory()->create();
        $other->assignRole('operator');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        Livewire::actingAs($operator)->test(OperatorTable::class)->set('targetOperatorId', $other->id);
    }

    public function test_workspace_view_is_read_only_even_for_admin(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $work = Work::create(['status' => 'Da Lavorare']);
        $work->users()->attach($operator->id);

        Livewire::actingAs($this->superAdmin())
            ->test(OperatorTable::class, ['targetOperatorId' => $operator->id, 'readOnlyMode' => true])
            ->call('takeWork', $work->id)
            ->assertForbidden();

        $this->assertSame('Da Lavorare', $work->fresh()->status);
    }

    public function test_operator_report_without_activity_permission_does_not_receive_timeline_data(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        Livewire::actingAs($supervisor)->test(OperatorStats::class)->assertNotDispatched('timeline-data');
        Livewire::actingAs($this->superAdmin())->test(OperatorStats::class)->assertDispatched('timeline-data');

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        Livewire::actingAs($operator)->test(OperatorStats::class)->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Permessi Ente
    // ---------------------------------------------------------------------

    public function test_permessi_operator_never_receives_or_writes_economic_fields(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('permessi ente');

        $permesso = PermessoEnte::create([
            'progetto' => 'ORIGINALE',
            'status' => 'Da Lavorare',
            'al_dl' => 500,
            'vdc1' => 42,
        ]);
        $permesso->users()->attach($operator->id);

        Livewire::actingAs($operator)
            ->test(PermessoEnteForm::class, ['isEdit' => true])
            ->call('editPermesso', $permesso->id)
            ->assertSet('progetto', 'ORIGINALE')
            ->assertSet('al_dl', null)
            ->assertSet('vdc1', null)
            ->set('al_dl', 1)
            ->set('vdc1', 1)
            ->set('acception_date', '2020-01-01 10:00:00')
            ->set('progetto', 'MODIFICATO')
            ->call('save')
            ->assertHasNoErrors();

        $permesso->refresh();
        $this->assertSame('MODIFICATO', $permesso->progetto);
        $this->assertEquals(500, $permesso->al_dl);
        $this->assertEquals(42, $permesso->vdc1);
        $this->assertNull($permesso->acception_date);
    }

    public function test_permessi_table_has_no_economic_or_operator_date_filters_without_permission(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('permessi ente');

        $table = Livewire::actingAs($operator)->test(PermessiEnteTable::class)->instance();
        $filters = collect($table->filters())->map->getKey()->all();
        $columns = collect($table->columns())->map->getTitle()->all();

        foreach (['al_dl_filter', 'a_ne_filter', 'delta_filter', 'vdc1_filter', 'mese_saldo_filter', 'pagato_dl_filter', 'acception_filter', 'completion_filter'] as $key) {
            $this->assertNotContains($key, $filters);
        }
        foreach (['Al DL', 'A NE', 'Delta', 'VDC1', 'Mese Saldo', 'Pagato DL', 'Presa in Carico Op.'] as $title) {
            $this->assertNotContains($title, $columns);
        }

        $adminTable = Livewire::actingAs($this->superAdmin())->test(PermessiEnteTable::class)->instance();
        $this->assertContains('al_dl_filter', collect($adminTable->filters())->map->getKey()->all());
        $this->assertContains('Al DL', collect($adminTable->columns())->map->getTitle()->all());
    }

    public function test_permessi_export_redacts_finance_and_scopes_to_assigned_records(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('permessi ente');
        $mine = PermessoEnte::create(['progetto' => 'MIO', 'status' => 'Da Lavorare', 'al_dl' => 500]);
        $mine->users()->attach($operator->id);
        PermessoEnte::create(['progetto' => 'ALTRUI', 'status' => 'Da Lavorare']);

        $export = new PermessiEnteExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $operator);

        $this->assertSame(['MIO'], $export->query()->pluck('progetto')->all());
        $this->assertNotContains('Al DL', $export->headings());
        $this->assertNotContains('Data PiC', $export->headings());
        $this->assertCount(count($export->headings()), $export->map($mine->fresh(['users'])));

        $full = new PermessiEnteExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $this->superAdmin());
        $this->assertContains('Al DL', $full->headings());
        $this->assertCount(2, $full->query()->get());
    }

    public function test_admin_cannot_take_permesso_without_operator_permission(): void
    {
        $permesso = PermessoEnte::create(['progetto' => 'X', 'status' => 'Da Lavorare']);

        $this->assertFalse($this->superAdmin()->can('take', $permesso));
    }

    // ---------------------------------------------------------------------
    // GBX
    // ---------------------------------------------------------------------

    public function test_gbx_user_never_receives_or_writes_value_and_payments(): void
    {
        $company = Company::create(['name' => 'Impresa GBX']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('GBX');
        $gbx = Gbx::create(['company_id' => $company->id, 'network' => 'N1', 'value' => 1000, 'company_paid' => 10]);

        Livewire::actingAs($user)
            ->test(GbxEdit::class)
            ->call('editGbx', $gbx->id)
            ->assertSet('network', 'N1')
            ->assertSet('value', null)
            ->assertSet('company_paid', null)
            ->set('value', 1)
            ->set('network', 'N2')
            ->call('update')
            ->assertHasNoErrors();

        $gbx->refresh();
        $this->assertSame('N2', $gbx->network);
        $this->assertEquals(1000, $gbx->value);
        $this->assertEquals(10, $gbx->company_paid);

        Livewire::actingAs($user)
            ->test(GbxForm::class)
            ->set('company_id', $company->id)
            ->set('network', 'NUOVO')
            ->set('value', 999)
            ->call('store')
            ->assertHasNoErrors();

        $this->assertNull(Gbx::where('network', 'NUOVO')->value('value'));
    }

    public function test_gbx_export_redacts_finance_and_respects_company_scope(): void
    {
        $company = Company::create(['name' => 'Impresa GBX']);
        $other = Company::create(['name' => 'Altra Impresa']);
        $user = User::factory()->create(['company_id' => $company->id]);
        $user->assignRole('GBX');
        $mine = Gbx::create(['company_id' => $company->id, 'network' => 'MIO', 'value' => 1000]);
        Gbx::create(['company_id' => $other->id, 'network' => 'ALTRUI', 'value' => 5]);

        $export = new GbxExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $user);

        $this->assertSame(['MIO'], $export->query()->pluck('network')->all());
        $this->assertNotContains('Valore', $export->headings());
        $this->assertCount(count($export->headings()), $export->map($mine->fresh()));
    }

    // ---------------------------------------------------------------------
    // Tabelle di base
    // ---------------------------------------------------------------------

    public function test_base_tables_user_cannot_reach_rates(): void
    {
        $user = $this->userWithPermissions([P::SETTINGS_BASE_TABLES]);

        Livewire::actingAs($user)->test(AdminBaseTables::class)->call('setTab', 'CompanyWorkPhaseRate')->assertForbidden();
        Livewire::actingAs($user)->test(AdminBaseTables::class)->call('saveProductionTarget')->assertForbidden();
        Livewire::actingAs($user)->test(AdminBaseTables::class)->call('saveRate', 1, 1)->assertForbidden();

        $this->expectException(CannotUpdateLockedPropertyException::class);
        Livewire::actingAs($user)->test(AdminBaseTables::class)->set('activeTab', 'ProductionTarget');
    }

    public function test_rates_only_user_opens_on_a_rate_tab(): void
    {
        $user = $this->userWithPermissions([P::SETTINGS_RATES]);

        $this->actingAs($user)->get(route('admin.base-tables'))->assertOk();

        Livewire::actingAs($user)
            ->test(AdminBaseTables::class)
            ->assertSet('activeTab', 'CompanyWorkPhaseRate')
            ->call('setTab', 'Central')
            ->assertForbidden();
    }

    // ---------------------------------------------------------------------
    // Utenti e ruoli: solo super admin
    // ---------------------------------------------------------------------

    public function test_user_table_is_super_admin_only_and_cannot_delete_super_admins(): void
    {
        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        $admin = $this->superAdmin();
        $otherAdmin = $this->superAdmin();
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::actingAs($supervisor)->test(UserTable::class)->assertForbidden();

        Livewire::actingAs($admin)->test(UserTable::class)->call('deleteUser', $otherAdmin->id)->assertForbidden();
        Livewire::actingAs($admin)->test(UserTable::class)->call('deleteUser', $admin->id)->assertForbidden();
        Livewire::actingAs($admin)->test(UserTable::class)->call('deleteUser', $operator->id)->assertOk();

        $this->assertModelExists($otherAdmin);
        $this->assertModelMissing($operator);
    }

    public function test_role_manager_creates_roles_with_catalog_permissions_only(): void
    {
        $component = Livewire::actingAs($this->superAdmin())
            ->test(RoleManager::class)
            ->set('newRoleName', 'Responsabile Deco')
            ->call('createRole')
            ->assertHasNoErrors()
            ->set('selectedPermissions', [P::DECO_VIEW_ALL, P::DECO_FINANCE_EDIT, 'permesso.inventato', P::SUPER_ADMIN_ROLE])
            ->call('savePermissions');

        $role = Role::findByName('Responsabile Deco');
        $this->assertEqualsCanonicalizing(
            [P::DECO_ACCESS, P::DECO_VIEW_ALL, P::DECO_FINANCE_VIEW, P::DECO_FINANCE_EDIT],
            $role->permissions->pluck('name')->all()
        );
        $component->assertSet('selectedPermissions', P::withImplied([P::DECO_VIEW_ALL, P::DECO_FINANCE_EDIT]));
    }

    public function test_role_manager_protects_the_super_admin_role(): void
    {
        $adminRole = Role::findByName('admin');
        $before = $adminRole->permissions->pluck('name')->sort()->values()->all();

        Livewire::actingAs($this->superAdmin())
            ->test(RoleManager::class)
            ->call('selectRole', $adminRole->id)
            ->set('selectedPermissions', [])
            ->call('savePermissions')
            ->assertForbidden();

        Livewire::actingAs($this->superAdmin())->test(RoleManager::class)->call('deleteRole', $adminRole->id)->assertForbidden();

        Livewire::actingAs($this->superAdmin())
            ->test(RoleManager::class)
            ->set('newRoleName', 'admin')
            ->call('createRole')
            ->assertHasErrors('newRoleName');

        $this->assertSame($before, $adminRole->fresh()->permissions->pluck('name')->sort()->values()->all());
    }

    public function test_role_manager_does_not_delete_roles_in_use_and_is_super_admin_only(): void
    {
        $deco = User::factory()->create();
        $deco->assignRole('Deco');
        $decoRole = Role::findByName('Deco');

        Livewire::actingAs($this->superAdmin())
            ->test(RoleManager::class)
            ->call('deleteRole', $decoRole->id)
            ->assertHasErrors('delete');

        $this->assertModelExists($decoRole);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');
        Livewire::actingAs($supervisor)->test(RoleManager::class)->assertForbidden();
        $this->actingAs($supervisor)->get(route('admin.roles'))->assertForbidden();
    }

    public function test_implied_permissions_close_dependency_chains(): void
    {
        $this->assertEqualsCanonicalizing(
            [P::PERMESSI_ACCESS, P::PERMESSI_FINANCE_VIEW, P::PERMESSI_FINANCE_EDIT],
            P::withImplied([P::PERMESSI_FINANCE_EDIT])
        );
        $this->assertEqualsCanonicalizing(
            [P::DECO_ACCESS, P::DECO_FINANCE_VIEW, P::DECO_REPORT],
            P::withImplied([P::DECO_REPORT])
        );
        $this->assertSame([], P::withImplied(['qualcosa.che.non.esiste']));
    }

    // ---------------------------------------------------------------------

    private function userWithPermissions(array $permissions): User
    {
        $role = Role::create(['name' => 'Ruolo test '.uniqid()]);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    /**
     * Riporta il database allo stato precedente al rework: vecchi permessi e vecchio seeder.
     */
    private function resetToLegacyPermissions(): void
    {
        Permission::query()->delete();
        Role::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['get works', 'get permessi ente', 'get gbx', 'get decommissioning'] as $name) {
            Permission::create(['name' => $name]);
        }

        Role::create(['name' => 'admin'])->givePermissionTo(['get gbx', 'get decommissioning']);
        Role::create(['name' => 'supervisor'])->givePermissionTo('get works');
        Role::create(['name' => 'operator'])->givePermissionTo('get works');
        Role::create(['name' => 'freelance operator'])->givePermissionTo('get works');
        Role::create(['name' => 'permessi ente'])->givePermissionTo('get permessi ente');
        Role::create(['name' => 'GBX'])->givePermissionTo('get gbx');
        Role::create(['name' => 'GBX Supervisor'])->givePermissionTo('get gbx');
        Role::create(['name' => 'Deco'])->givePermissionTo('get decommissioning');
    }

    private function refreshUsers(User ...$users): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($users as $user) {
            $user->unsetRelation('roles')->unsetRelation('permissions');
        }
    }
}
