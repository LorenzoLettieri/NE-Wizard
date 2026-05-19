<?php

namespace Tests\Feature;

use App\Models\Work;
use App\Models\MediaUploadSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\FakeStoredFile;
use Tests\Support\HandlesMediaUploadsHarness;
use Tests\Support\HandlesMediaUploadsTestComponent;
use Tests\Support\UsesMysqlTestDatabase;
use Tests\TestCase;

class MediaUploadHardeningTest extends TestCase
{
    use RefreshDatabase, UsesMysqlTestDatabase {
        UsesMysqlTestDatabase::beforeRefreshingDatabase insteadof RefreshDatabase;
    }

    public function test_file_count_limit_is_rejected(): void
    {
        Storage::fake('local');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Upload count validation',
        ]);

        $files = [];

        for ($index = 0; $index < 11; $index++) {
            $files[] = UploadedFile::fake()->create("doc-{$index}.pdf", 50, 'application/pdf');
        }

        Livewire::test(HandlesMediaUploadsTestComponent::class, ['work' => $work])
            ->set('files', $files)
            ->assertHasErrors(['files' => 'max'])
            ->assertSet('files', [])
            ->assertSet('uploadMessageType', 'danger');
    }

    public function test_persist_rolls_back_database_and_storage_when_store_fails_mid_batch(): void
    {
        Storage::fake('local');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Rollback coverage',
        ]);

        $harness = new HandlesMediaUploadsHarness();
        $harness->files = [
            new FakeStoredFile('ok.pdf', 'application/pdf', 100, 'ok.pdf'),
            new FakeStoredFile('broken.pdf', 'application/pdf', 100, null),
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to store uploaded media.');

        try {
            $harness->persistMediaForModel($work, 'works_media');
        } finally {
            $this->assertDatabaseCount('media', 0);
            Storage::disk('local')->assertMissing('works_media/ok.pdf');
        }
    }

    public function test_trait_claims_completed_chunked_upload_sessions(): void
    {
        Storage::fake('local');

        $work = Work::create([
            'status' => 'Da Lavorare',
            'notes' => 'Trait claim coverage',
        ]);

        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        Storage::disk('local')->put('works_media/trait-claim.txt', 'trait');

        MediaUploadSession::create([
            'id' => 'trait-session',
            'user_id' => $user->id,
            'context' => 'work',
            'mediable_type' => Work::class,
            'form_token' => 'trait-token',
            'original_name' => 'trait-claim.txt',
            'mime_type' => 'text/plain',
            'size' => 5,
            'chunk_size' => 1024,
            'chunk_count' => 1,
            'received_chunks' => 1,
            'status' => 'completed',
            'final_path' => 'works_media/trait-claim.txt',
            'completed_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $harness = new HandlesMediaUploadsHarness();
        $harness->initializeChunkedMediaUploads('work');
        $harness->mediaUploadFormToken = 'trait-token';

        $this->assertSame(1, $harness->claimChunkedMediaForModel($work));

        $this->assertDatabaseHas('media', [
            'mediable_type' => Work::class,
            'mediable_id' => $work->id,
            'file_path' => 'works_media/trait-claim.txt',
        ]);
    }
}
