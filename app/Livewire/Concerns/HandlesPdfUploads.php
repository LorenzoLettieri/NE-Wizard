<?php

namespace App\Livewire\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

trait HandlesPdfUploads
{
    public $files = [];
    public $uploadMessage = null;
    public $uploadMessageType = 'info';
    public $pendingMediaRemovalIds = [];

    protected function pdfUploadValidationRules(): array
    {
        return [
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf|max:10240',
        ];
    }

    public function updatedFiles(): void
    {
        try {
            $this->validate($this->pdfUploadValidationRules());
            $this->setPendingUploadMessage();
        } catch (ValidationException $exception) {
            $this->files = [];
            $this->uploadMessage = 'Upload non valido: sono ammessi solo file PDF fino a 10 MB.';
            $this->uploadMessageType = 'danger';

            throw $exception;
        }
    }

    public function removePendingFile(int $index): void
    {
        if (! isset($this->files[$index])) {
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

        foreach ($this->files as $file) {
            $path = $file->store($directory, 'public');

            $model->media()->create([
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            $uploadedCount++;
        }

        $this->files = [];
        $this->clearUploadFeedback();

        return $uploadedCount;
    }

    protected function deleteMediaFromModel(Model $model, int $mediaId): void
    {
        $media = $model->media()->findOrFail($mediaId);

        Storage::disk('public')->delete($media->file_path);
        $media->delete();
    }

    public function toggleMediaRemoval(int $mediaId): void
    {
        if (in_array($mediaId, $this->pendingMediaRemovalIds, true)) {
            $this->pendingMediaRemovalIds = array_values(array_filter(
                $this->pendingMediaRemovalIds,
                fn (int $id) => $id !== $mediaId
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

            if (! $media) {
                continue;
            }

            Storage::disk('public')->delete($media->file_path);
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
