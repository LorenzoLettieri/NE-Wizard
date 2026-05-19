<?php

namespace App\Livewire\Concerns;

use App\Models\Media;
use App\Services\ChunkedMediaUploadService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

trait HandlesMediaUploads
{
    public $files = [];
    public $uploadMessage = null;
    public $uploadMessageType = 'info';
    public $pendingMediaRemovalIds = [];
    public ?string $mediaUploadContext = null;
    public ?int $mediaUploadModelId = null;
    public ?string $mediaUploadFormToken = null;
    public array $completedUploadSessionIds = [];

    protected function mediaUploadValidationRules(): array
    {
        return [
            'files' => ['nullable', 'array', 'max:10'],
            'files.*' => [
                'file',
                'max:25600'
            ],
        ];
    }

    public function updatedFiles(): void
    {
        try {
            $this->validate($this->mediaUploadValidationRules());
            $this->setPendingUploadMessage();
        } catch (ValidationException $exception) {
            $this->files = [];
            $this->uploadMessage = 'Upload non valido: sono ammessi PDF, immagini JPG/PNG e documenti Office fino a 10 MB, massimo 10 file.';
            $this->uploadMessageType = 'danger';

            throw $exception;
        }
    }

    public function removePendingFile(int $index): void
    {
        if (!isset($this->files[$index])) {
            return;
        }

        $files = $this->files;
        unset($files[$index]);

        $this->files = array_values($files);

        if (count($this->files) === 0) {
            $this->clearUploadFeedback();

            return;
        }

        $this->setPendingUploadMessage();
    }

    protected function persistUploadedFiles(Model $model, string $directory): int
    {
        $uploadedCount = 0;
        $storedPaths = [];

        try {
            DB::transaction(function () use ($model, $directory, &$uploadedCount, &$storedPaths): void {
                foreach ($this->files as $file) {
                    $path = $file->store($directory, Media::PRIVATE_DISK);

                    if (!is_string($path) || $path === '') {
                        throw new RuntimeException('Unable to store uploaded media.');
                    }

                    $storedPaths[] = $path;

                    $model->media()->create([
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ]);

                    $uploadedCount++;
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                if (Storage::disk(Media::PRIVATE_DISK)->exists($path)) {
                    Storage::disk(Media::PRIVATE_DISK)->delete($path);
                }
            }

            throw $exception;
        }

        $this->files = [];
        $this->clearUploadFeedback();

        return $uploadedCount;
    }

    public function initializeChunkedMediaUploads(string $context, ?int $modelId = null): void
    {
        $this->mediaUploadContext = $context;
        $this->mediaUploadModelId = $modelId;
        $this->mediaUploadFormToken ??= (string) Str::uuid();
        $this->completedUploadSessionIds = [];
    }

    public function registerCompletedUploadSession(string $sessionId): void
    {
        if (! in_array($sessionId, $this->completedUploadSessionIds, true)) {
            $this->completedUploadSessionIds[] = $sessionId;
        }
    }

    public function unregisterCompletedUploadSession(string $sessionId): void
    {
        $this->completedUploadSessionIds = array_values(array_filter(
            $this->completedUploadSessionIds,
            fn (string $id) => $id !== $sessionId
        ));
    }

    protected function claimCompletedUploadSessions(Model $model): int
    {
        if (! auth()->check() || ! $this->mediaUploadContext || ! $this->mediaUploadFormToken) {
            return 0;
        }

        $claimed = app(ChunkedMediaUploadService::class)->claimCompletedSessions(
            $model,
            auth()->user(),
            $this->mediaUploadContext,
            $this->mediaUploadFormToken
        );

        $this->completedUploadSessionIds = [];

        return $claimed;
    }

    protected function deleteMediaFromModel(Model $model, int $mediaId): void
    {
        $media = $model->media()->findOrFail($mediaId);

        $media->deleteStoredFilesIfPresentOrFail();
        $media->delete();
    }

    public function toggleMediaRemoval(int $mediaId): void
    {
        if (in_array($mediaId, $this->pendingMediaRemovalIds, true)) {
            $this->pendingMediaRemovalIds = array_values(array_filter(
                $this->pendingMediaRemovalIds,
                fn(int $id) => $id !== $mediaId
            ));

            return;
        }

        $this->pendingMediaRemovalIds[] = $mediaId;
        $this->pendingMediaRemovalIds = array_values(array_unique($this->pendingMediaRemovalIds));
    }

    protected function commitPendingMediaRemovals(Model $model): int
    {
        $removedCount = 0;

        foreach ($this->pendingMediaRemovalIds as $mediaId) {
            $media = $model->media()->find($mediaId);

            if (!$media) {
                continue;
            }

            $media->deleteStoredFilesIfPresentOrFail();
            $media->delete();
            $removedCount++;
        }

        $this->pendingMediaRemovalIds = [];

        return $removedCount;
    }

    protected function clearPendingMediaRemovals(): void
    {
        $this->pendingMediaRemovalIds = [];
    }

    protected function setPendingUploadMessage(): void
    {
        $count = count($this->files);

        if ($count === 0) {
            $this->clearUploadFeedback();

            return;
        }

        $this->uploadMessage = $count === 1
            ? '1 allegato selezionato correttamente. Salva per confermare il caricamento.'
            : "{$count} allegati selezionati correttamente. Salva per confermare il caricamento.";
        $this->uploadMessageType = 'success';
    }

    protected function clearUploadFeedback(): void
    {
        $this->uploadMessage = null;
        $this->uploadMessageType = 'info';
    }
}
