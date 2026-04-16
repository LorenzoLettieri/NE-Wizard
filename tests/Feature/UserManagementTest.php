<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_a_user_with_a_new_password_hashes_it(): void
    {
        $admin = $this->seededAdmin();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put(route('updateUser', $user->id), $this->updatePayload($user, [
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]))
            ->assertRedirect(route('accounts-table'));

        $freshUser = $user->fresh();

        $this->assertNotSame($originalPassword, $freshUser->password);
        $this->assertTrue(Hash::check('new-password', $freshUser->password));
    }

    public function test_updating_a_user_with_mismatched_password_confirmation_is_rejected(): void
    {
        $admin = $this->seededAdmin();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put(route('updateUser', $user->id), $this->updatePayload($user, [
                'password' => 'new-password',
                'password_confirmation' => 'different-password',
            ]))
            ->assertInvalid(['password']);

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_updating_a_user_without_a_password_keeps_the_existing_hash(): void
    {
        $admin = $this->seededAdmin();
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $originalPassword = $user->password;

        $this->actingAs($admin)
            ->put(route('updateUser', $user->id), $this->updatePayload($user, [
                'password' => '',
            ]))
            ->assertRedirect(route('accounts-table'));

        $this->assertSame($originalPassword, $user->fresh()->password);
    }

    public function test_updating_a_user_email_rejects_a_duplicate_email(): void
    {
        $admin = $this->seededAdmin();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('updateUser', $user->id), $this->updatePayload($user, [
                'email' => $otherUser->email,
            ]))
            ->assertInvalid(['email']);
    }

    public function test_updating_a_user_email_allows_the_users_own_email(): void
    {
        $admin = $this->seededAdmin();
        $user = User::factory()->create();

        $this->actingAs($admin)
            ->put(route('updateUser', $user->id), $this->updatePayload($user))
            ->assertRedirect(route('accounts-table'));

        $this->assertSame($user->email, $user->fresh()->email);
    }

    private function seededAdmin(): User
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'company_id' => $user->company_id,
            'roles' => ['admin'],
        ], $overrides);
    }
}
