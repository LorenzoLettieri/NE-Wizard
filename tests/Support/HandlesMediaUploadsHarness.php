<?php

namespace Tests\Support;

use App\Livewire\Concerns\HandlesMediaUploads;
use Illuminate\Database\Eloquent\Model;

class HandlesMediaUploadsHarness
{
    use HandlesMediaUploads;

    public function mediaRulesForTesting(): array
    {
        return $this->mediaUploadValidationRules();
    }

    public function persistMediaForModel(Model $model, string $directory): int
    {
        return $this->persistUploadedFiles($model, $directory);
    }

    public function removeMediaFromModel(Model $model, int $mediaId): void
    {
        $this->deleteMediaFromModel($model, $mediaId);
    }

    public function claimChunkedMediaForModel(Model $model): int
    {
        return $this->claimCompletedUploadSessions($model);
    }
}
