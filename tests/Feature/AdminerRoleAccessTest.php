<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Onecentlin\Adminer\Http\Controllers\AdminerController;
use Tests\TestCase;

#[\PHPUnit\Framework\Attributes\RequiresPhpExtension('pdo_sqlite')]
class AdminerRoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_adminer_forbids_authenticated_non_admin_users(): void
    {
        $this->seed(RoleSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)
            ->get(route('adminer::index'))
            ->assertForbidden();
    }

    public function test_adminer_is_available_to_admin_users_only(): void
    {
        $this->seed(RoleSeeder::class);

        // Adminer vero chiama exit() e terminerebbe l'intera suite PHPUnit (falso verde).
        // Il fake eredita il costruttore, quindi gli stessi middleware: si verifica solo l'accesso.
        $this->app->bind(AdminerController::class, fn () => new class extends AdminerController
        {
            public function index()
            {
                return response('Adminer');
            }
        });

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('adminer::index'))
            ->assertOk()
            ->assertSee('Adminer', false);
    }
}
