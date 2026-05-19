<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaUploadSession;
use App\Models\User;
use App\Models\Work;
use App\Services\ChunkedMediaUploadService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UsesMysqlTestDatabase;
use Tests\TestCase;

class ChunkedMediaUploadTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_media_upload_session_can_be_persisted(): void
    {
        $user = User::factory()->create();

        $session = MediaUploadSession::create([
            'id' => 'upload-test-1',
            'user_id' => $user->id,
            'context' => 'work',
            'form_token' => 'form-token',
            'original_name' => 'report.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1234,
            'chunk_size' => 512,
            'chunk_count' => 3,
            'received_chunks' => 0,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame('upload-test-1', $session->id);
        $this->assertSame('work', $session->context);
        $this->assertSame(1234, $session->size);
    }

    public function test_chunked_upload_initialization_rejects_files_over_500_mb(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('media.uploads.process', [
                'context' => 'work',
                'form_token' => 'form-token',
            ]), [], [
                'Upload-Length' => (string) (500 * 1024 * 1024 + 1),
                'Upload-Name' => 'too-big.pdf',
            ])
            ->assertStatus(422);
    }

    public function test_filepond_initialization_can_start_without_upload_name_header(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('media.uploads.process', [
                'context' => 'work',
                'form_token' => 'form-token',
            ]), [], [
                'Upload-Length' => '12',
            ])
            ->assertOk();

        $session = MediaUploadSession::findOrFail(trim($response->getContent()));

        $this->assertSame('allegato', $session->original_name);
    }

    public function test_chunked_upload_accepts_any_file_extension(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $initResponse = $this->actingAs($admin)
            ->post(route('media.uploads.process', [
                'context' => 'work',
                'form_token' => 'form-token',
            ]), [], [
                'Upload-Length' => '12',
            ])
            ->assertOk();

        $this->actingAs($admin)
            ->call('PATCH', route('media.uploads.patch', trim($initResponse->getContent())), [], [], [], [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_UPLOAD_OFFSET' => '0',
                'HTTP_UPLOAD_LENGTH' => '12',
                'HTTP_UPLOAD_NAME' => 'payload.exe',
            ], 'test payload')
            ->assertOk();

        $this->assertDatabaseHas('media_upload_sessions', [
            'id' => trim($initResponse->getContent()),
            'original_name' => 'payload.exe',
            'status' => 'completed',
        ]);
    }

    public function test_chunked_upload_processes_chunks_and_creates_media_for_existing_record(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Chunk target',
        ]);

        $initResponse = $this->actingAs($admin)
            ->post(route('media.uploads.process', [
                'context' => 'work',
                'model_id' => $work->id,
            ]), [], [
                'Upload-Length' => '11',
                'Upload-Metadata' => 'mime_type ' . base64_encode('application/pdf'),
            ])
            ->assertOk();

        $sessionId = trim($initResponse->getContent());

        $this->actingAs($admin)
            ->call('PATCH', route('media.uploads.patch', $sessionId), [], [], [], [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_UPLOAD_OFFSET' => '0',
                'HTTP_UPLOAD_LENGTH' => '11',
                'HTTP_UPLOAD_NAME' => 'chunked.pdf',
            ], 'hello ')
            ->assertOk();

        $this->actingAs($admin)
            ->call('HEAD', route('media.uploads.head', $sessionId))
            ->assertOk()
            ->assertHeader('Upload-Offset', '6');

        $this->actingAs($admin)
            ->call('PATCH', route('media.uploads.patch', $sessionId), [], [], [], [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_UPLOAD_OFFSET' => '6',
                'HTTP_UPLOAD_LENGTH' => '11',
                'HTTP_UPLOAD_NAME' => 'chunked.pdf',
            ], 'world')
            ->assertOk();

        $media = Media::query()->where('mediable_type', Work::class)->where('mediable_id', $work->id)->first();

        $this->assertNotNull($media);
        $this->assertSame('chunked.pdf', $media->file_name);
        $this->assertSame(11, $media->size);
        Storage::disk('local')->assertExists($media->file_path);
        $this->assertSame('hello world', Storage::disk('local')->get($media->file_path));
    }

    public function test_chunked_upload_cancel_removes_temporary_chunks(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('local');

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $initResponse = $this->actingAs($admin)
            ->post(route('media.uploads.process', [
                'context' => 'work',
                'form_token' => 'form-token',
            ]), [], [
                'Upload-Length' => '6',
            ])
            ->assertOk();

        $sessionId = trim($initResponse->getContent());

        $this->actingAs($admin)
            ->call('PATCH', route('media.uploads.patch', $sessionId), [], [], [], [
                'CONTENT_TYPE' => 'application/offset+octet-stream',
                'HTTP_UPLOAD_OFFSET' => '0',
                'HTTP_UPLOAD_LENGTH' => '6',
                'HTTP_UPLOAD_NAME' => 'cancel.pdf',
            ], 'abc')
            ->assertOk();

        Storage::disk('local')->assertExists("chunked_uploads/{$sessionId}/chunks/0.part");

        $this->actingAs($admin)
            ->call('DELETE', route('media.uploads.revert'), [], [], [], [], $sessionId)
            ->assertNoContent();

        Storage::disk('local')->assertMissing("chunked_uploads/{$sessionId}/chunks/0.part");

        $this->assertDatabaseHas('media_upload_sessions', [
            'id' => $sessionId,
            'status' => 'cancelled',
        ]);
    }

    public function test_completed_create_flow_sessions_can_be_claimed_for_new_model(): void
    {
        Storage::fake('local');

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Claim target',
        ]);

        Storage::disk('local')->put('works_media/claimable.txt', 'claimable');
        Storage::disk('local')->put('works_media/wrong-user.txt', 'wrong user');

        MediaUploadSession::create([
            'id' => 'claimable-session',
            'user_id' => $owner->id,
            'context' => 'work',
            'mediable_type' => Work::class,
            'form_token' => 'form-token',
            'original_name' => 'claimable.txt',
            'mime_type' => 'text/plain',
            'size' => 9,
            'chunk_size' => 1024,
            'chunk_count' => 1,
            'received_chunks' => 1,
            'status' => 'completed',
            'final_path' => 'works_media/claimable.txt',
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        MediaUploadSession::create([
            'id' => 'wrong-user-session',
            'user_id' => $otherUser->id,
            'context' => 'work',
            'mediable_type' => Work::class,
            'form_token' => 'form-token',
            'original_name' => 'wrong-user.txt',
            'mime_type' => 'text/plain',
            'size' => 10,
            'chunk_size' => 1024,
            'chunk_count' => 1,
            'received_chunks' => 1,
            'status' => 'completed',
            'final_path' => 'works_media/wrong-user.txt',
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $claimed = app(ChunkedMediaUploadService::class)
            ->claimCompletedSessions($work, $owner, 'work', 'form-token');

        $this->assertSame(1, $claimed);
        $this->assertDatabaseHas('media', [
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/claimable.txt',
            'file_name' => 'claimable.txt',
        ]);
        $this->assertDatabaseHas('media_upload_sessions', [
            'id' => 'claimable-session',
            'status' => 'attached',
            'mediable_id' => $work->id,
        ]);
        $this->assertDatabaseMissing('media', [
            'file_path' => 'works_media/wrong-user.txt',
        ]);
    }

    public function test_cleanup_command_removes_expired_incomplete_sessions_and_chunks(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();

        MediaUploadSession::create([
            'id' => 'expired-session',
            'user_id' => $user->id,
            'context' => 'work',
            'mediable_type' => Work::class,
            'form_token' => 'expired-token',
            'original_name' => 'expired.txt',
            'mime_type' => 'text/plain',
            'size' => 6,
            'chunk_size' => 1024,
            'chunk_count' => 1,
            'received_chunks' => 1,
            'status' => 'uploading',
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        MediaUploadSession::create([
            'id' => 'completed-session',
            'user_id' => $user->id,
            'context' => 'work',
            'mediable_type' => Work::class,
            'form_token' => 'completed-token',
            'original_name' => 'completed.txt',
            'mime_type' => 'text/plain',
            'size' => 9,
            'chunk_size' => 1024,
            'chunk_count' => 1,
            'received_chunks' => 1,
            'status' => 'completed',
            'final_path' => 'works_media/completed.txt',
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        Storage::disk('local')->put('chunked_uploads/expired-session/chunks/0.part', 'expire');
        Storage::disk('local')->put('works_media/completed.txt', 'completed');

        $this->artisan('media:cleanup-chunked-uploads --hours=24')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('media_upload_sessions', [
            'id' => 'expired-session',
        ]);
        $this->assertDatabaseHas('media_upload_sessions', [
            'id' => 'completed-session',
        ]);
        Storage::disk('local')->assertMissing('chunked_uploads/expired-session/chunks/0.part');
        Storage::disk('local')->assertExists('works_media/completed.txt');
    }
}
