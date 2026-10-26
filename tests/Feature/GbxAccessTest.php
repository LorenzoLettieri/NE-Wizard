<?php

namespace Tests\Feature;

use App\Livewire\GbxEdit;
use App\Livewire\GbxTable;
use App\Livewire\ViewGbx;
use App\Models\Company;
use App\Models\Gbx;
use App\Models\Media;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class GbxAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $ownCompany;

    private Company $otherCompany;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->ownCompany = Company::create(['name' => 'Impresa Uno']);
        $this->otherCompany = Company::create(['name' => 'Impresa Due']);
    }

    private function gbxUser(?Company $company, string $role = 'GBX'): User
    {
        $user = User::factory()->create(['company_id' => $company?->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_gbx_user_can_view_and_edit_gbx_of_own_company(): void
    {
        $gbx = Gbx::create(['company_id' => $this->ownCompany->id, 'network' => 'NTW-OWN']);

        $this->actingAs($this->gbxUser($this->ownCompany));

        Livewire::test(ViewGbx::class)
            ->call('viewGbx', $gbx->id)
            ->assertOk()
            ->assertSee('NTW-OWN');

        Livewire::test(GbxEdit::class)
            ->call('editGbx', $gbx->id)
            ->set('network', 'NTW-OWN-2')
            ->call('update')
            ->assertHasNoErrors();

        $this->assertSame('NTW-OWN-2', $gbx->fresh()->network);
    }

    public function test_gbx_user_cannot_view_gbx_of_another_company(): void
    {
        $gbx = Gbx::create(['company_id' => $this->otherCompany->id, 'network' => 'NTW-OTHER']);

        $this->actingAs($this->gbxUser($this->ownCompany));

        Livewire::test(ViewGbx::class)
            ->call('viewGbx', $gbx->id)
            ->assertForbidden();
    }

    public function test_gbx_user_cannot_edit_gbx_of_another_company(): void
    {
        $gbx = Gbx::create(['company_id' => $this->otherCompany->id, 'network' => 'NTW-OTHER']);

        $this->actingAs($this->gbxUser($this->ownCompany));

        Livewire::test(GbxEdit::class)
            ->call('editGbx', $gbx->id)
            ->assertForbidden();

        $this->assertSame('NTW-OTHER', $gbx->fresh()->network);
    }

    public function test_gbx_supervisor_can_view_gbx_of_any_company(): void
    {
        $gbx = Gbx::create(['company_id' => $this->otherCompany->id, 'network' => 'NTW-OTHER']);

        $this->actingAs($this->gbxUser(null, 'GBX Supervisor'));

        Livewire::test(ViewGbx::class)
            ->call('viewGbx', $gbx->id)
            ->assertOk()
            ->assertSee('NTW-OTHER');
    }

    public function test_only_admin_can_delete_gbx(): void
    {
        $gbx = Gbx::create(['company_id' => $this->ownCompany->id]);

        $this->actingAs($this->gbxUser($this->ownCompany))
            ->delete(route('deleteGbx', $gbx))
            ->assertForbidden();

        $this->assertDatabaseHas('gbxes', ['id' => $gbx->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->delete(route('deleteGbx', $gbx))
            ->assertRedirect();

        $this->assertDatabaseMissing('gbxes', ['id' => $gbx->id]);
    }

    public function test_gbx_user_cannot_download_media_of_another_company(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('gbx_media/other.txt', 'private');

        $gbx = Gbx::create(['company_id' => $this->otherCompany->id]);
        $media = Media::create([
            'mediable_type' => Gbx::class,
            'mediable_id' => $gbx->id,
            'file_path' => 'gbx_media/other.txt',
            'file_name' => 'other.txt',
            'mime_type' => 'text/plain',
            'size' => 7,
        ]);

        $this->actingAs($this->gbxUser($this->ownCompany))
            ->get(route('media.download', $media))
            ->assertForbidden();
    }

    public function test_gbx_user_cannot_start_upload_on_gbx_of_another_company(): void
    {
        $gbx = Gbx::create(['company_id' => $this->otherCompany->id]);

        $this->actingAs($this->gbxUser($this->ownCompany))
            ->withHeaders(['Upload-Length' => '10', 'Upload-Name' => 'file.txt'])
            ->post(route('media.uploads.process', ['context' => 'gbx', 'model_id' => $gbx->id]))
            ->assertForbidden();
    }

    public function test_gbx_table_shows_only_own_company_and_survives_users_without_company(): void
    {
        Gbx::create(['company_id' => $this->ownCompany->id, 'network' => 'NTW-OWN']);
        Gbx::create(['company_id' => $this->otherCompany->id, 'network' => 'NTW-OTHER']);

        $this->actingAs($this->gbxUser($this->ownCompany));

        Livewire::test(GbxTable::class)
            ->assertSee('NTW-OWN')
            ->assertDontSee('NTW-OTHER');

        // Prima un utente GBX senza impresa mandava in errore la tabella (company->name su null)
        $this->actingAs($this->gbxUser(null));

        Livewire::test(GbxTable::class)
            ->assertOk()
            ->assertDontSee('NTW-OWN')
            ->assertDontSee('NTW-OTHER');
    }
}
