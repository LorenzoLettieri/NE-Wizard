<?php

namespace App\Services;

use App\Models\Decommissioning;
use App\Models\Gbx;
use App\Models\Media;
use App\Models\MediaUploadSession;
use App\Models\PermessoEnte;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ChunkedMediaUploadService
{
    public const MAX_FILE_BYTES = 524288000;
    public const TOTAL_FORM_BYTES = 1073741824;
    public const DEFAULT_CHUNK_BYTES = 10485760;
    private const CONTEXTS = [
        'work' => [Work::class, 'works_media'],
        'gbx' => [Gbx::class, 'gbx_media'],
        'permesso_ente' => [PermessoEnte::class, 'permessi_ente_media'],
        'decommissioning' => [Decommissioning::class, 'decommissioning_media'],
    ];

    public function initialize(User $user, Request $request): MediaUploadSession
    {
        $context = (string) $request->query('context');
        $this->assertKnownContext($context);

        $size = (int) $request->header('Upload-Length', 0);
        $originalName = $this->sanitizeOriginalName((string) $request->header('Upload-Name', 'allegato'));
        $formToken = $request->query('form_token') ? (string) $request->query('form_token') : null;
        $model = $this->resolveModel($context, $request->query('model_id'));

        if ($size < 1 || $size > self::MAX_FILE_BYTES) {
            abort(422, 'Ogni allegato puo pesare al massimo 500 MB.');
        }

        if (! $model && $formToken === null) {
            abort(422, 'Token form mancante per upload in creazione.');
        }

        $this->authorizeContext($user, $context, $model);
        $this->assertTotalQuota($user, $context, $formToken, $model, $size);

        [$modelClass] = self::CONTEXTS[$context];

        return MediaUploadSession::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'context' => $context,
            'mediable_type' => $model ? $model::class : $modelClass,
            'mediable_id' => $model?->getKey(),
            'form_token' => $formToken,
            'original_name' => $originalName,
            'mime_type' => $this->mimeTypeFromMetadata((string) $request->header('Upload-Metadata')),
            'size' => $size,
            'chunk_size' => self::DEFAULT_CHUNK_BYTES,
            'chunk_count' => (int) ceil($size / self::DEFAULT_CHUNK_BYTES),
            'received_chunks' => 0,
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
    }

    public function writeChunk(User $user, MediaUploadSession $session, Request $request): int
    {
        $this->assertSessionOwner($user, $session);

        if (! in_array($session->status, ['pending', 'uploading'], true)) {
            abort(409, 'Upload session is not writable.');
        }

        $offset = (int) $request->header('Upload-Offset', -1);
        $length = (int) $request->header('Upload-Length', $session->size);
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);

        if ($contentLength > self::DEFAULT_CHUNK_BYTES) {
            abort(413, 'Upload chunk is too large.');
        }

        $content = $request->getContent();

        if ($offset < 0 || $length !== $session->size || $content === '') {
            abort(422, 'Invalid chunk request.');
        }

        if ($offset > $session->size) {
            abort(416, 'Invalid upload offset.');
        }

        $uploadName = $request->header('Upload-Name');

        if ($uploadName) {
            $originalName = $this->sanitizeOriginalName((string) $uploadName);

            if ($session->original_name !== $originalName) {
                $session->forceFill([
                    'original_name' => $originalName,
                ])->save();
            }
        }

        $chunkPath = $this->chunkPath($session, $offset);
        Storage::disk(Media::PRIVATE_DISK)->put($chunkPath, $content);

        $session->forceFill([
            'status' => 'uploading',
            'received_chunks' => count(Storage::disk(Media::PRIVATE_DISK)->files($this->chunkDirectory($session))),
        ])->save();

        $nextOffset = $this->nextOffset($session);

        if ($nextOffset >= $session->size) {
            $this->complete($session);
        }

        return $nextOffset;
    }

    public function nextOffset(MediaUploadSession $session): int
    {
        $disk = Storage::disk(Media::PRIVATE_DISK);
        $offset = 0;

        while ($disk->exists($this->chunkPath($session, $offset))) {
            $offset += $disk->size($this->chunkPath($session, $offset));
        }

        return $offset;
    }

    public function cancel(User $user, string $sessionId): void
    {
        $session = MediaUploadSession::findOrFail($sessionId);
        $this->assertSessionOwner($user, $session);

        if ($session->final_path) {
            Media::query()
                ->where('file_path', $session->final_path)
                ->delete();

            Storage::disk(Media::PRIVATE_DISK)->delete($session->final_path);
        }

        Storage::disk(Media::PRIVATE_DISK)->deleteDirectory($this->baseDirectory($session));

        $session->forceFill([
            'status' => 'cancelled',
        ])->save();
    }

    public function claimCompletedSessions(Model $model, User $user, string $context, string $formToken): int
    {
        $claimed = 0;

        MediaUploadSession::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->where('form_token', $formToken)
            ->where('status', 'completed')
            ->whereNotNull('final_path')
            ->orderBy('created_at')
            ->get()
            ->each(function (MediaUploadSession $session) use ($model, &$claimed): void {
                $model->media()->create([
                    'file_path' => $session->final_path,
                    'file_name' => $session->original_name,
                    'mime_type' => $session->mime_type,
                    'size' => $session->size,
                ]);

                $session->forceFill([
                    'mediable_type' => $model::class,
                    'mediable_id' => $model->getKey(),
                    'status' => 'attached',
                ])->save();

                $claimed++;
            });

        return $claimed;
    }

    public function cleanupExpired(int $hours): int
    {
        $cutoff = now()->subHours($hours);
        $deleted = 0;

        MediaUploadSession::query()
            ->whereIn('status', ['pending', 'uploading', 'failed', 'cancelled'])
            ->where(function ($query) use ($cutoff): void {
                $query->where('expires_at', '<=', now())
                    ->orWhere('updated_at', '<=', $cutoff);
            })
            ->get()
            ->each(function (MediaUploadSession $session) use (&$deleted): void {
                Storage::disk(Media::PRIVATE_DISK)->deleteDirectory($this->baseDirectory($session));
                $session->delete();
                $deleted++;
            });

        return $deleted;
    }

    private function complete(MediaUploadSession $session): void
    {
        if ($session->status === 'completed' || $session->status === 'attached') {
            return;
        }

        $finalPath = $this->finalPath($session);
        $disk = Storage::disk(Media::PRIVATE_DISK);
        $disk->makeDirectory(dirname($finalPath));

        $finalStream = fopen($disk->path($finalPath), 'wb');

        if ($finalStream === false) {
            throw new RuntimeException('Unable to create final media file.');
        }

        try {
            $offset = 0;

            while ($offset < $session->size) {
                $chunkPath = $this->chunkPath($session, $offset);

                if (! $disk->exists($chunkPath)) {
                    throw new RuntimeException('Missing upload chunk.');
                }

                $chunkStream = fopen($disk->path($chunkPath), 'rb');

                if ($chunkStream === false) {
                    throw new RuntimeException('Unable to read upload chunk.');
                }

                try {
                    stream_copy_to_stream($chunkStream, $finalStream);
                } finally {
                    fclose($chunkStream);
                }

                $offset += $disk->size($chunkPath);
            }
        } catch (\Throwable $exception) {
            fclose($finalStream);
            $disk->delete($finalPath);
            $session->forceFill(['status' => 'failed'])->save();

            throw $exception;
        }

        fclose($finalStream);

        if ($disk->size($finalPath) !== $session->size) {
            $disk->delete($finalPath);
            $session->forceFill(['status' => 'failed'])->save();
            throw new RuntimeException('Assembled media size mismatch.');
        }

        DB::transaction(function () use ($session, $finalPath): void {
            $session->forceFill([
                'status' => 'completed',
                'final_path' => $finalPath,
                'completed_at' => now(),
            ])->save();

            if ($session->mediable_id !== null) {
                $model = $session->mediable_type::findOrFail($session->mediable_id);

                $model->media()->create([
                    'file_path' => $finalPath,
                    'file_name' => $session->original_name,
                    'mime_type' => $session->mime_type,
                    'size' => $session->size,
                ]);

                $session->forceFill(['status' => 'attached'])->save();
            }
        });

        $disk->deleteDirectory($this->baseDirectory($session));
    }

    private function assertKnownContext(string $context): void
    {
        if (! array_key_exists($context, self::CONTEXTS)) {
            abort(422, 'Contesto upload non valido.');
        }
    }

    private function resolveModel(string $context, mixed $modelId): ?Model
    {
        if (! $modelId) {
            return null;
        }

        [$modelClass] = self::CONTEXTS[$context];

        return $modelClass::findOrFail($modelId);
    }

    private function authorizeContext(User $user, string $context, ?Model $model): void
    {
        if ($model instanceof Work) {
            abort_unless($user->can('update', $model), 403);

            return;
        }

        $authorized = match ($context) {
            'work' => $user->hasAnyRole(['admin', 'supervisor']),
            'gbx' => $user->hasAnyRole(['admin', 'GBX', 'GBX Supervisor']),
            'permesso_ente' => $user->hasAnyRole(['admin', 'permessi ente']),
            'decommissioning' => $user->hasAnyRole(['admin', 'Deco']),
            default => false,
        };

        abort_unless($authorized, 403);
    }

    private function assertSessionOwner(User $user, MediaUploadSession $session): void
    {
        abort_unless((int) $session->user_id === (int) $user->id, 403);

        if ($session->expires_at->isPast()) {
            abort(410, 'Upload session expired.');
        }
    }

    private function assertTotalQuota(User $user, string $context, ?string $formToken, ?Model $model, int $newSize): void
    {
        $query = MediaUploadSession::query()
            ->where('user_id', $user->id)
            ->where('context', $context)
            ->whereIn('status', ['pending', 'uploading', 'completed']);

        if ($model) {
            $query->where('mediable_type', $model::class)->where('mediable_id', $model->getKey());
        } else {
            $query->where('form_token', $formToken);
        }

        if ((int) $query->sum('size') + $newSize > self::TOTAL_FORM_BYTES) {
            abort(422, 'Il totale degli allegati selezionati non puo superare 1 GB.');
        }
    }

    private function mimeTypeFromMetadata(string $metadata): ?string
    {
        foreach (explode(',', $metadata) as $entry) {
            [$key, $value] = array_pad(explode(' ', trim($entry), 2), 2, null);

            if ($key === 'mime_type' && is_string($value)) {
                return base64_decode($value, true) ?: null;
            }
        }

        return null;
    }

    private function sanitizeOriginalName(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', $name) ?: 'allegato';

        return trim($name) !== '' ? trim($name) : 'allegato';
    }

    private function finalPath(MediaUploadSession $session): string
    {
        [, $directory] = self::CONTEXTS[$session->context];

        return $directory . '/' . $session->id . '_' . $session->original_name;
    }

    private function baseDirectory(MediaUploadSession $session): string
    {
        return "chunked_uploads/{$session->id}";
    }

    private function chunkDirectory(MediaUploadSession $session): string
    {
        return $this->baseDirectory($session) . '/chunks';
    }

    private function chunkPath(MediaUploadSession $session, int $offset): string
    {
        return $this->chunkDirectory($session) . "/{$offset}.part";
    }
}
