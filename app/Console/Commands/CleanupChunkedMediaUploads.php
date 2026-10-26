<?php

namespace App\Console\Commands;

use App\Services\ChunkedMediaUploadService;
use Illuminate\Console\Command;

class CleanupChunkedMediaUploads extends Command
{
    protected $signature = 'media:cleanup-chunked-uploads
        {--hours=24 : Minimum age in hours for incomplete sessions}
        {--include-unclaimed : Also remove completed uploads never attached to a record (manual only)}
        {--unclaimed-hours=168 : Minimum age in hours for unclaimed completed uploads}
        {--dry-run : With --include-unclaimed, only count what would be removed}';

    protected $description = 'Remove expired incomplete chunked media upload sessions and temporary chunks.';

    public function handle(ChunkedMediaUploadService $uploads): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $deleted = $uploads->cleanupExpired($hours);

        $this->info("Removed {$deleted} expired chunked media upload session(s).");

        if ($this->option('include-unclaimed')) {
            $unclaimedHours = max(24, (int) $this->option('unclaimed-hours'));
            $dryRun = (bool) $this->option('dry-run');
            $unclaimed = $uploads->cleanupUnclaimedCompleted($unclaimedHours, $dryRun);

            $this->info($dryRun
                ? "Would remove {$unclaimed} unclaimed completed upload(s) older than {$unclaimedHours}h."
                : "Removed {$unclaimed} unclaimed completed upload(s) older than {$unclaimedHours}h.");
        }

        return self::SUCCESS;
    }
}
