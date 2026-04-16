<?php

namespace App\Services;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LegacyMediaMigrationService
{
    public function migrateAll(int $chunkSize = 100): array
    {
        $summary = [
            'migrated' => 0,
            'cleaned_public_duplicates' => 0,
            'already_private' => 0,
            'missing' => 0,
        ];

        Media::query()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($mediaItems) use (&$summary): void {
                foreach ($mediaItems as $media) {
                    $outcome = $this->migrateMedia($media);
                    $summary[$outcome]++;
                }
            });

        return $summary;
    }

    public function migrateMedia(Media $media): string
    {
        $privateDisk = Storage::disk(Media::PRIVATE_DISK);
        $legacyDisk = Storage::disk(Media::LEGACY_PUBLIC_DISK);
        $privateExists = $media->existsOnPrivateDisk();
        $legacyExists = $media->existsOnLegacyPublicDisk();
        $hadLegacyPublicCopy = $legacyExists;

        if (! $privateExists && ! $legacyExists) {
            return 'missing';
        }

        $copiedToPrivate = false;

        if (! $privateExists && $legacyExists) {
            $stream = $legacyDisk->readStream($media->file_path);

            if ($stream === false) {
                throw new RuntimeException("Unable to read legacy media [{$media->id}] from public storage.");
            }

            try {
                $written = $privateDisk->writeStream($media->file_path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($written === false) {
                throw new RuntimeException("Unable to migrate legacy media [{$media->id}] to private storage.");
            }

            $copiedToPrivate = true;
            $privateExists = true;
        }

        if ($legacyExists) {
            $deleted = $legacyDisk->delete($media->file_path);

            if ($deleted === false) {
                if ($copiedToPrivate && $privateDisk->exists($media->file_path)) {
                    $privateDisk->delete($media->file_path);
                }

                throw new RuntimeException("Unable to remove legacy public media [{$media->id}] after migration.");
            }
        }

        if ($copiedToPrivate) {
            return 'migrated';
        }

        return $hadLegacyPublicCopy ? 'cleaned_public_duplicates' : 'already_private';
    }
}
