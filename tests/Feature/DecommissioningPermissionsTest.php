<?php

namespace Tests\Feature;

use App\Exports\DecommissioningExport;
use App\Livewire\DecommissioningForm;
use App\Livewire\DecommissioningTable;
use App\Models\Decommissioning;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog as P;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Ruoli personalizzati sul Decommissioning: visibilità, azioni e contabilità sono indipendenti.
 */
#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class DecommissioningPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_admin_receives_every_non_personal_permission(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(P::SUPER_ADMIN_ROLE);

        foreach (array_diff(P::all(), P::personal()) as $permission) {
            $this->assertTrue($admin->can($permission), "L'admin dovrebbe avere {$permission}");
        }

        $this->assertFalse($admin->can(P::DECO_ASSIGNABLE));
    }

    public function test_sync_is_idempotent(): void
    {
        P::sync();
        P::sync();

        $this->assertSame(count(P::all()), \Spatie\Permission\Models\Permission::count());
    }

    public function test_manager_role_sees_every_decommissioning_without_economic_columns(): void
    {
        $manager = $this->managerUser();
        $designer = $this->designerUser();

        Decommissioning::create(['clli' => 'DECO-OF-OTHERS', 'status' => 'Da Lavorare', 'progettista_id' => $designer->id]);
        Decommissioning::create(['clli' => 'DECO-UNASSIGNED', 'status' => 'Da Lavorare']);

        $this->actingAs($manager)->get(route('decommissionings.table'))->assertOk();

        Livewire::actingAs($manager)
            ->test(DecommissioningTable::class)
            ->assertSee('DECO-OF-OTHERS')
            ->assertSee('DECO-UNASSIGNED')
            ->assertDontSee('Tot NE')
            ->assertDontSee('Agio')
            ->assertDontSee('Pagata Prog');
    }

    public function test_economic_values_never_reach_the_livewire_state_without_finance_permission(): void
    {
        $manager = $this->managerUser();
        $designer = $this->designerUser();

        $record = $this->recordWithEconomics($designer);

        foreach ([$manager, $designer] as $user) {
            Livewire::actingAs($user)
                ->test(DecommissioningForm::class, ['isShow' => true])
                ->call('viewDecommissioning', $record->id)
                ->assertSet('clli', 'DECO-MONEY')
                ->assertSet('tot_ne', 0)
                ->assertSet('tot_prog', 0)
                ->assertSet('agio', 0)
                ->assertSet('ne_amount_1', 0)
                ->assertSet('pagata_ne', '0')
                ->assertSet('val', '0')
                ->assertDontSee('Contabilità Admin')
                ->assertDontSee('777');
        }
    }

    public function test_finance_viewer_sees_economic_values(): void
    {
        $manager = $this->managerUser([P::DECO_FINANCE_VIEW]);
        $record = $this->recordWithEconomics($this->designerUser());

        Livewire::actingAs($manager)
            ->test(DecommissioningForm::class, ['isShow' => true])
            ->call('viewDecommissioning', $record->id)
            ->assertSet('tot_ne', '777.00')
            ->assertSet('pagata_ne', '1')
            ->assertSet('val', '1')
            ->assertSee('Contabilità Admin');
    }

    public function test_manager_edit_keeps_payment_flags_and_val_and_ignores_forged_amounts(): void
    {
        $manager = $this->managerUser();
        $record = $this->recordWithEconomics($this->designerUser());

        Livewire::actingAs($manager)
            ->test(DecommissioningForm::class, ['isEdit' => true])
            ->call('editDecommissioning', $record->id)
            ->set('note_permesso', 'Aggiornato dal responsabile')
            ->set('pagata_ne', '0')
            ->set('val', '0')
            ->set('ne_amount_1', 99999)
            ->call('save')
            ->assertHasNoErrors();

        $record->refresh();

        $this->assertSame('Aggiornato dal responsabile', $record->note_permesso);
        $this->assertTrue((bool) $record->pagata_ne);
        $this->assertTrue((bool) $record->val);
        $this->assertNotEquals('99999.00', $record->ne_amount_1);
    }

    public function test_read_only_role_cannot_edit_or_create(): void
    {
        $viewer = $this->userWithPermissions('Deco Viewer', [P::DECO_ACCESS, P::DECO_VIEW_ALL]);
        $record = Decommissioning::create(['clli' => 'DECO-RO', 'status' => 'Da Lavorare']);

        $this->actingAs($viewer)->get(route('decommissionings.table'))->assertOk();
        $this->actingAs($viewer)->get(route('addDecommissioning'))->assertForbidden();
        $this->actingAs($viewer)->get(route('editDecommissioning', $record->id))->assertForbidden();

        Livewire::actingAs($viewer)
            ->test(DecommissioningForm::class, ['isEdit' => true])
            ->call('editDecommissioning', $record->id)
            ->assertForbidden();
    }

    public function test_manager_cannot_delete_or_open_the_economic_report(): void
    {
        $manager = $this->managerUser([P::DECO_REPORT]);
        $record = Decommissioning::create(['clli' => 'DECO-DEL', 'status' => 'Da Lavorare']);

        $this->actingAs($manager)
            ->delete(route('deleteDecommissioning', $record))
            ->assertForbidden();

        // deco.report da solo non basta: il report è fatto di importi
        $this->actingAs($manager)
            ->get(route('reports.decommissioning'))
            ->assertForbidden();

        $this->assertDatabaseHas('decommissionings', ['id' => $record->id]);
    }

    public function test_manager_export_has_no_economic_columns(): void
    {
        Excel::fake();
        $manager = $this->managerUser([P::DECO_EXPORT]);

        $this->actingAs($manager)
            ->get(route('exports.decommissionings', ['date_field' => 'created_at', 'start' => '2026-04-01', 'end' => '2026-04-14']))
            ->assertOk();

        Excel::assertDownloaded('decommissionings_created_at_2026-04-01_2026-04-14.xlsx', function (DecommissioningExport $export) {
            return ! array_intersect(['Tot Prog', 'Tot NE', 'Agio', 'Pagata NE', 'Val', 'NE 1.0'], $export->headings())
                && in_array('CLLI', $export->headings(), true);
        });
    }

    public function test_export_rows_stay_aligned_with_headings_without_finance(): void
    {
        $record = $this->recordWithEconomics($this->designerUser());

        $export = new DecommissioningExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $this->managerUser());

        $this->assertNotContains('Tot NE', $export->headings());
        $this->assertCount(count($export->headings()), $export->map($record->fresh()));
        $this->assertNotContains(777.0, array_map(fn ($v) => is_numeric($v) ? (float) $v : $v, $export->map($record->fresh())));

        $full = new DecommissioningExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $this->superAdmin());
        $this->assertContains('Tot NE', $full->headings());
        $this->assertCount(count($full->headings()), $full->map($record->fresh()));
        $this->assertArrayHasKey('H', $export->columnFormats());
    }

    public function test_export_is_scoped_to_own_records_without_view_all(): void
    {
        $designer = $this->designerUser();
        $other = $this->designerUser();

        Decommissioning::create(['clli' => 'MINE', 'status' => 'Da Lavorare', 'progettista_id' => $designer->id]);
        Decommissioning::create(['clli' => 'NOT-MINE', 'status' => 'Da Lavorare', 'progettista_id' => $other->id]);

        $export = new DecommissioningExport('created_at', now()->subDay(), now()->addDay(), visibleTo: $designer);

        $this->assertSame(['MINE'], $export->query()->pluck('clli')->all());
    }

    public function test_designer_list_follows_assignable_permission_not_role_name(): void
    {
        $designer = $this->designerUser();
        $custom = $this->userWithPermissions('Progettista esterno', [P::DECO_ACCESS, P::DECO_ASSIGNABLE]);
        $manager = $this->managerUser();

        $designers = Livewire::actingAs($manager)->test(DecommissioningForm::class)->instance()->progettisti();

        $this->assertArrayHasKey($designer->id, $designers);
        $this->assertArrayHasKey($custom->id, $designers);
        $this->assertArrayNotHasKey($manager->id, $designers);
    }

    private function managerUser(array $extra = []): User
    {
        return $this->userWithPermissions('Responsabile Deco '.count($extra).uniqid(), array_merge([
            P::DECO_ACCESS,
            P::DECO_VIEW_ALL,
            P::DECO_CREATE,
            P::DECO_EDIT,
        ], $extra));
    }

    private function designerUser(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Deco');

        return $user;
    }

    private function userWithPermissions(string $roleName, array $permissions): User
    {
        $role = Role::create(['name' => $roleName]);
        $role->givePermissionTo($permissions);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function recordWithEconomics(User $designer): Decommissioning
    {
        return Decommissioning::create([
            'clli' => 'DECO-MONEY',
            'status' => 'Da Lavorare',
            'progettista_id' => $designer->id,
            'ne_amount_1' => 777,
            'tot_ne' => 777,
            'tot_prog' => 500,
            'agio' => 277,
            'pagata_ne' => true,
            'val' => true,
        ]);
    }
}
