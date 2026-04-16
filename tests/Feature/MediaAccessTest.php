<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Models\Work;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesMysqlTestDatabase;
use Tests\TestCase;

class MediaAccessTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_assigned_operator_can_download_work_media_via_authorized_route(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Attachment owner',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->put('works_media/private-note.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/private-note.txt',
            'file_name' => 'private-note.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->actingAs($operator)
            ->get(route('media.download', $media))
            ->assertOk()
            ->assertDownload('private-note.txt');
    }

    public function test_unassigned_operator_cannot_download_work_media(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $assignedOperator = User::factory()->create();
        $assignedOperator->assignRole('operator');

        $otherOperator = User::factory()->create();
        $otherOperator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Restricted attachment',
        ]);
        $work->users()->attach($assignedOperator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->put('works_media/restricted.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/restricted.txt',
            'file_name' => 'restricted.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->actingAs($otherOperator)
            ->get(route('media.download', $media))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_work_media(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Guest denied',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('local')->put('works_media/guest-denied.txt', 'private attachment');

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/guest-denied.txt',
            'file_name' => 'guest-denied.txt',
            'mime_type' => 'text/plain',
            'size' => 18,
        ]);

        $this->get(route('media.download', $media))
            ->assertRedirect('/');
    }

    public function test_authorized_download_does_not_serve_legacy_public_media_before_migration(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Legacy public attachment',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Storage::disk('public')->put('works_media/legacy-public.txt', 'legacy attachment');

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/legacy-public.txt',
            'file_name' => 'legacy-public.txt',
            'mime_type' => 'text/plain',
            'size' => 17,
        ]);

        $this->actingAs($operator)
            ->get(route('media.download', $media))
            ->assertNotFound();
    }

    public function test_legacy_media_migration_command_moves_tracked_public_media_to_private_storage(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Legacy public attachment',
        ]);

        Storage::disk('public')->put('works_media/legacy-public.txt', 'legacy attachment');

        Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/legacy-public.txt',
            'file_name' => 'legacy-public.txt',
            'mime_type' => 'text/plain',
            'size' => 17,
        ]);

        $this->artisan('media:migrate-private')
            ->assertExitCode(0);

        Storage::disk('local')->assertExists('works_media/legacy-public.txt');
        Storage::disk('public')->assertMissing('works_media/legacy-public.txt');
    }

    public function test_authorized_download_returns_not_found_when_media_file_is_missing(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Missing attachment',
        ]);
        $work->users()->attach($operator->id, [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/missing.txt',
            'file_name' => 'missing.txt',
            'mime_type' => 'text/plain',
            'size' => 0,
        ]);

        $this->actingAs($operator)
            ->get(route('media.download', $media))
            ->assertNotFound();
    }

    public function test_stale_media_row_can_be_removed_when_no_file_exists(): void
    {
        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Stale attachment row',
        ]);

        $media = Media::create([
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/stale.txt',
            'file_name' => 'stale.txt',
            'mime_type' => 'text/plain',
            'size' => 0,
        ]);

        $harness = new \Tests\Support\HandlesMediaUploadsHarness();
        $harness->removeMediaFromModel($work, $media->id);

        $this->assertDatabaseMissing('media', [
            'id' => $media->id,
        ]);
    }
}
