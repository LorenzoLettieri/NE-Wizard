<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Media extends Model
{
    public const PRIVATE_DISK = 'local';

    public const LEGACY_PUBLIC_DISK = 'public';

    protected $fillable = [
        'file_path',
        'file_name',
        'mime_type',
        'size',
        'mediable_id',
        'mediable_type',
    ];

    /**
     * Get the parent mediable model.
     */
    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function existsOnPrivateDisk(): bool
    {
        return Storage::disk(self::PRIVATE_DISK)->exists($this->file_path);
    }

    public function existsOnLegacyPublicDisk(): bool
    {
        return Storage::disk(self::LEGACY_PUBLIC_DISK)->exists($this->file_path);
    }

    public function ensurePrivateAvailability(): ?string
    {
        $privateDisk = Storage::disk(self::PRIVATE_DISK);
        $legacyDisk = Storage::disk(self::LEGACY_PUBLIC_DISK);
        $privateExists = $this->existsOnPrivateDisk();
        $legacyExists = $this->existsOnLegacyPublicDisk();

        if (! $privateExists && ! $legacyExists) {
            return null;
        }

        $copiedToPrivate = false;

        if (! $privateExists && $legacyExists) {
            $stream = $legacyDisk->readStream($this->file_path);

            if ($stream === false) {
                throw new RuntimeException("Unable to read legacy media [{$this->id}] from public storage.");
            }

            try {
                $written = $privateDisk->writeStream($this->file_path, $stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }

            if ($written === false) {
                throw new RuntimeException("Unable to migrate legacy media [{$this->id}] to private storage.");
            }

            $copiedToPrivate = true;
        }

        if ($legacyExists) {
            $deleted = $legacyDisk->delete($this->file_path);

            if ($deleted === false) {
                if ($copiedToPrivate && $privateDisk->exists($this->file_path)) {
                    $privateDisk->delete($this->file_path);
                }

                throw new RuntimeException("Unable to remove legacy public media [{$this->id}] after migration.");
            }
        }

        return self::PRIVATE_DISK;
    }

    public function deleteStoredFilesIfPresentOrFail(): void
    {
        foreach ([self::PRIVATE_DISK, self::LEGACY_PUBLIC_DISK] as $diskName) {
            $disk = Storage::disk($diskName);

            if (! $disk->exists($this->file_path)) {
                continue;
            }

            $deleted = $disk->delete($this->file_path);

            if ($deleted === false) {
                throw new RuntimeException("Unable to delete media [{$this->id}] from disk [{$diskName}].");
            }
        }
    }
}
