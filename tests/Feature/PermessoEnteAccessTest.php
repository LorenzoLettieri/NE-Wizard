<?php

namespace Tests\Feature;

use App\Livewire\PermessiEnteTable;
use App\Livewire\PermessoEnteForm;
use App\Models\Media;
use App\Models\PermessoEnte;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class PermessoEnteAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private User $otherOperator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->operator = User::factory()->create();
        $this->operator->assignRole('permessi ente');

        $this->otherOperator = User::factory()->create();
        $this->otherOperator->assignRole('permessi ente');
    }

    public function test_non_admin_users_see_only_permessi_assigned_to_them(): void
    {
        $this->createPermesso('PE-MINE', $this->operator);
        $this->createPermesso('PE-FOREIGN', $this->otherOperator);

        $this->actingAs($this->operator);

        Livewire::test(PermessiEnteTable::class)
            ->assertSee('PE-MINE')
            ->assertDontSee('PE-FOREIGN');

        $this->actingAs($this->admin);

        Livewire::test(PermessiEnteTable::class)
            ->assertSee('PE-MINE')
            ->assertSee('PE-FOREIGN');
    }

    public function test_non_admin_users_cannot_open_or_edit_permessi_not_assigned_to_them(): void
    {
        $assigned = $this->createPermesso('PE-MINE', $this->operator);
        $foreign = $this->createPermesso('PE-FOREIGN', $this->otherOperator);

        $this->actingAs($this->operator);

        $this->get(route('editPermesso', $foreign->id))->assertForbidden();

        Livewire::test(PermessoEnteForm::class)
            ->call('viewPermesso', $foreign->id)
            ->assertForbidden();

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $foreign->id)
            ->assertForbidden();

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $assigned->id)
            ->set('permessoEnteId', $foreign->id)
            ->set('progetto', 'TAMPERED')
            ->call('save')
            ->assertForbidden();

        $this->assertSame('PE-FOREIGN', $foreign->fresh()->progetto);

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $assigned->id)
            ->set('progetto', 'PE-MINE-UPDATED')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('PE-MINE-UPDATED', $assigned->fresh()->progetto);
    }

    public function test_table_actions_are_limited_to_assigned_operators(): void
    {
        $foreign = $this->createPermesso('PE-FOREIGN', $this->otherOperator);
        $assigned = $this->createPermesso('PE-MINE', $this->operator);

        $this->actingAs($this->operator);

        Livewire::test(PermessiEnteTable::class)
            ->call('takePermesso', $foreign->id)
            ->assertDispatched('error');

        $this->assertSame('Da Lavorare', $foreign->fresh()->status);
        $this->assertFalse($foreign->users()->whereKey($this->operator->id)->exists());

        Livewire::test(PermessiEnteTable::class)
            ->call('takePermesso', $assigned->id)
            ->assertNotDispatched('error');

        $this->assertSame('In Lavorazione', $assigned->fresh()->status);

        $foreign->update(['status' => 'In Lavorazione']);

        Livewire::test(PermessiEnteTable::class)
            ->call('consegnaPermesso', $foreign->id)
            ->assertDispatched('error');

        $this->assertSame('In Lavorazione', $foreign->fresh()->status);

        Livewire::test(PermessiEnteTable::class)
            ->call('consegnaPermesso', $assigned->id)
            ->assertNotDispatched('error');

        $this->assertSame('Consegnato', $assigned->fresh()->status);

        Livewire::test(PermessiEnteTable::class)
            ->call('endPermesso', $assigned->id)
            ->assertDispatched('error');

        $this->assertSame('Consegnato', $assigned->fresh()->status);

        $this->actingAs($this->admin);

        Livewire::test(PermessiEnteTable::class)
            ->call('endPermesso', $assigned->id)
            ->assertNotDispatched('error');

        $this->assertSame('Fine Lavori', $assigned->fresh()->status);
    }

    public function test_only_admin_can_delete_permessi(): void
    {
        $permesso = $this->createPermesso('PE-DELETE', $this->operator);

        $this->actingAs($this->operator)
            ->delete(route('deletePermesso', $permesso))
            ->assertForbidden();

        $this->assertDatabaseHas('permessi_ente', ['id' => $permesso->id]);

        $this->actingAs($this->admin)
            ->delete(route('deletePermesso', $permesso))
            ->assertRedirect();

        $this->assertDatabaseMissing('permessi_ente', ['id' => $permesso->id]);
    }

    public function test_non_admin_users_are_assigned_to_the_permessi_they_create(): void
    {
        $this->actingAs($this->operator);

        Livewire::test(PermessoEnteForm::class)
            ->assertDontSee('Assegna Operatori')
            ->set('operator_id', [$this->otherOperator->id])
            ->set('progetto', 'PE-DEFAULT')
            ->call('save')
            ->assertRedirect(route('permessi-ente.table'));

        $permesso = PermessoEnte::where('progetto', 'PE-DEFAULT')->firstOrFail();

        $this->assertSame([$this->operator->id], $permesso->users()->pluck('users.id')->all());
    }

    public function test_only_admin_can_change_assigned_operators(): void
    {
        $permesso = $this->createPermesso('PE-ASSIGN', $this->operator);

        $this->actingAs($this->operator);

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $permesso->id)
            ->set('operator_id', [$this->otherOperator->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$this->operator->id], $permesso->users()->pluck('users.id')->all());

        $this->actingAs($this->admin);

        Livewire::test(PermessoEnteForm::class)
            ->assertSee('Assegna Operatori')
            ->call('editPermesso', $permesso->id)
            ->set('operator_id', [(string) $this->otherOperator->id])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([$this->otherOperator->id], $permesso->users()->pluck('users.id')->all());
    }

    public function test_admin_cannot_assign_users_not_enabled_for_permessi_ente(): void
    {
        $permesso = $this->createPermesso('PE-INVALID', $this->operator);

        $deco = User::factory()->create();
        $deco->assignRole('Deco');

        $this->actingAs($this->admin);

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $permesso->id)
            ->set('operator_id', [$deco->id])
            ->call('save')
            ->assertHasErrors(['operator_id.0' => 'in']);

        Livewire::test(PermessoEnteForm::class)
            ->call('editPermesso', $permesso->id)
            ->set('operator_id', [999999])
            ->call('save')
            ->assertHasErrors(['operator_id.0' => 'in']);

        $this->assertSame([$this->operator->id], $permesso->users()->pluck('users.id')->all());
    }

    public function test_edit_page_is_available_to_assigned_operators_and_admin(): void
    {
        $permesso = $this->createPermesso('PE-EDIT-PAGE', $this->operator);

        $this->actingAs($this->operator)
            ->get(route('editPermesso', $permesso->id))
            ->assertOk()
            ->assertSee('Modifica Permesso Ente #' . $permesso->id);

        $this->actingAs($this->admin)
            ->get(route('editPermesso', $permesso->id))
            ->assertOk();
    }

    public function test_only_admin_can_duplicate_permessi(): void
    {
        $permesso = $this->createPermesso('PE-ORIGINAL', $this->operator);
        $permesso->update([
            'status' => 'Fine Lavori',
            'acception_date' => now(),
            'delivery_date' => now(),
            'completion_date' => now(),
        ]);

        $this->actingAs($this->operator);

        Livewire::test(PermessiEnteTable::class)
            ->call('duplicatePermesso', $permesso->id)
            ->assertDispatched('error');

        $this->assertSame(1, PermessoEnte::count());

        $this->actingAs($this->admin);

        $component = Livewire::test(PermessiEnteTable::class)
            ->call('duplicatePermesso', $permesso->id);

        $copy = PermessoEnte::whereKeyNot($permesso->id)->firstOrFail();

        $component->assertRedirect(route('editPermesso', $copy->id));

        $this->assertSame('PE-ORIGINAL', $copy->progetto);
        $this->assertSame('Da Lavorare', $copy->status);
        $this->assertNull($copy->acception_date);
        $this->assertNull($copy->delivery_date);
        $this->assertNull($copy->completion_date);
        $this->assertSame([$this->operator->id], $copy->users()->pluck('users.id')->all());
    }

    public function test_supervisors_are_excluded_from_permessi_ente(): void
    {
        $permesso = $this->createPermesso('PE-SUPERVISOR', $this->operator);

        $supervisor = User::factory()->create();
        $supervisor->assignRole('supervisor');

        Storage::fake('local');
        Storage::disk('local')->put('permessi_ente_media/supervisor.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => PermessoEnte::class,
            'mediable_id' => $permesso->id,
            'file_path' => 'permessi_ente_media/supervisor.txt',
            'file_name' => 'supervisor.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->actingAs($supervisor);

        $this->get(route('permessi-ente.table'))->assertForbidden();
        $this->get(route('editPermesso', $permesso->id))->assertForbidden();
        $this->get(route('media.download', $media))->assertForbidden();

        $this->assertFalse($supervisor->can('view', $permesso));
        $this->assertFalse($supervisor->can('complete', $permesso));
        $this->assertFalse($supervisor->can('delete', $permesso));
    }

    public function test_permesso_media_download_is_limited_to_assigned_operators(): void
    {
        Storage::fake('local');

        $permesso = $this->createPermesso('PE-MEDIA', $this->operator);

        Storage::disk('local')->put('permessi_ente_media/allegato.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => PermessoEnte::class,
            'mediable_id' => $permesso->id,
            'file_path' => 'permessi_ente_media/allegato.txt',
            'file_name' => 'allegato.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->actingAs($this->operator)
            ->get(route('media.download', $media))
            ->assertOk()
            ->assertDownload('allegato.txt');

        $this->actingAs($this->otherOperator)
            ->get(route('media.download', $media))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->get(route('media.download', $media))
            ->assertOk();
    }

    private function createPermesso(string $progetto, User $operator): PermessoEnte
    {
        $permesso = PermessoEnte::create([
            'progetto' => $progetto,
            'status' => 'Da Lavorare',
        ]);
        $permesso->users()->attach($operator->id);

        return $permesso;
    }
}
