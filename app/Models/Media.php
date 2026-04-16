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
