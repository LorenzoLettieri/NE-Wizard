<?php

namespace Tests\Feature;

use App\Livewire\EditWork;
use App\Livewire\WorkForm;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkStatusHistory;
use App\Support\StatusBadge;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WorkFormValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
        $this->actingAs($this->admin);
    }

    public function test_work_can_be_created_without_touching_daphne_and_status(): void
    {
        // Prima: errore "Typed property $daphne must not be accessed before initialization"
        Livewire::test(WorkForm::class)
            ->set('network', 'NTW-DEFAULTS')
            ->call('store')
            ->assertHasNoErrors();

        $work = Work::where('network', 'NTW-DEFAULTS')->firstOrFail();

        $this->assertFalse($work->daphne);
        $this->assertSame('Da Lavorare', $work->status);
    }

    public function test_free_text_status_is_rejected_on_create(): void
    {
        Livewire::test(WorkForm::class)
            ->set('network', 'NTW-XSS')
            ->set('status', '<img src=x onerror=alert(1)>')
            ->call('store')
            ->assertHasErrors(['status']);

        $this->assertDatabaseMissing('works', ['network' => 'NTW-XSS']);
    }

    public function test_free_text_company_is_rejected_instead_of_breaking_the_insert(): void
    {
        Livewire::test(WorkForm::class)
            ->set('network', 'NTW-COMPANY')
            ->set('company_id', 'Impresa digitata a mano')
            ->call('store')
            ->assertHasErrors(['company_id']);
    }

    public function test_single_operator_value_is_still_accepted_on_create(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Livewire::test(WorkForm::class)
            ->set('network', 'NTW-ONE-OPERATOR')
            ->set('operator_id', $operator->id)
            ->call('store')
            ->assertHasNoErrors();

        $work = Work::where('network', 'NTW-ONE-OPERATOR')->firstOrFail();
        $this->assertTrue($work->users->contains($operator));
    }

    public function test_multiple_operators_can_be_assigned_on_create(): void
    {
        $first = User::factory()->create();
        $first->assignRole('operator');
        $second = User::factory()->create();
        $second->assignRole('operator');

        Livewire::test(WorkForm::class)
            ->set('network', 'NTW-TWO-OPERATORS')
            ->set('operator_id', [(string) $first->id, (string) $second->id])
            ->call('store')
            ->assertHasNoErrors();

        $this->assertCount(2, Work::where('network', 'NTW-TWO-OPERATORS')->firstOrFail()->users);
    }

    public function test_blank_daphne_on_edit_is_saved_as_no_instead_of_failing(): void
    {
        $work = Work::create(['status' => 'Da Lavorare', 'daphne' => true]);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('daphne', '0')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertFalse($work->fresh()->daphne);

        // Un valore vuoto forzato non arriva più al database (prima: violazione NOT NULL)
        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('daphne', '')
            ->call('update')
            ->assertHasErrors(['daphne']);
    }

    public function test_admin_cannot_save_an_empty_status(): void
    {
        $work = Work::create(['status' => 'In Lavorazione', 'daphne' => false]);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('status', '')
            ->call('update')
            ->assertHasErrors(['status']);

        $this->assertSame('In Lavorazione', $work->fresh()->status);
    }

    public function test_legacy_status_outside_the_list_can_be_kept_when_editing(): void
    {
        $work = Work::create(['status' => 'Stato Storico', 'daphne' => false]);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('notes', 'aggiornata')
            ->call('update')
            ->assertHasNoErrors();

        $work->refresh();
        $this->assertSame('Stato Storico', $work->status);
        $this->assertSame('aggiornata', $work->notes);
    }

    public function test_work_without_status_stays_editable_without_choosing_one(): void
    {
        $work = Work::create(['status' => null, 'daphne' => false]);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('notes', 'senza stato')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertNull($work->fresh()->status);
    }

    public function test_blank_nroe_is_stored_as_null_and_counts_as_one_for_accounting(): void
    {
        $work = Work::create(['status' => 'Da Lavorare', 'daphne' => false, 'nroe' => 3]);

        Livewire::test(EditWork::class)
            ->call('editWork', $work->id)
            ->set('nroe', '')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertNull($work->fresh()->nroe);
    }

    public function test_work_created_without_status_does_not_write_an_invalid_history_row(): void
    {
        $work = Work::create(['network' => 'NTW-NO-STATUS']);

        $this->assertSame(0, WorkStatusHistory::where('work_id', $work->id)->count());
    }

    public function test_status_badge_escapes_unknown_values(): void
    {
        $html = StatusBadge::work('<img src=x onerror=alert(1)>');

        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringContainsString('text-bg-success', StatusBadge::work('Consegnato'));
    }
}
