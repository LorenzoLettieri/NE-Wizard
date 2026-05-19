<?php

namespace App\Console\Commands;

use App\Services\ChunkedMediaUploadService;
use Illuminate\Console\Command;

class CleanupChunkedMediaUploads extends Command
{
    protected $signature = 'media:cleanup-chunked-uploads {--hours=24 : Minimum age in hours for incomplete sessions}';

    protected $description = 'Remove expired incomplete chunked media upload sessions and temporary chunks.';

    public function handle(ChunkedMediaUploadService $uploads): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $uploads->cleanupExpired($hours);

        $this->info("Removed {$deleted} expired chunked media upload session(s).");

        return self::SUCCESS;
    }
}
