<?php

namespace Tests\Support;

use App\Models\Media;
use Illuminate\Support\Facades\Storage;

class FakeStoredFile
{
    public function __construct(
        private readonly string $originalName,
        private readonly string $mimeType,
        private readonly int $size,
        private readonly ?string $storedPath = null,
    ) {
    }

    public function store(string $directory, string $disk): string
    {
        if ($this->storedPath === null) {
            return '';
        }

        $path = trim($directory . '/' . ltrim($this->storedPath, '/'), '/');
        Storage::disk($disk ?: Media::PRIVATE_DISK)->put($path, 'test payload');

        return $path;
    }

    public function getClientOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function getSize(): int
    {
        return $this->size;
    }
}
