<?php

namespace App\Console\Commands;

use App\Services\LegacyMediaMigrationService;
use Illuminate\Console\Command;

class MigratePrivateMedia extends Command
{
    protected $signature = 'media:migrate-private {--chunk=100 : Number of media rows to process per chunk}';

    protected $description = 'Move tracked legacy public media files to private storage and remove public copies.';

    public function handle(LegacyMediaMigrationService $migrationService): int
    {
        $chunkSize = max(1, (int) $this->option('chunk'));
        $summary = $migrationService->migrateAll($chunkSize);

        $this->table(['Outcome', 'Count'], [
            ['migrated', $summary['migrated']],
            ['cleaned_public_duplicates', $summary['cleaned_public_duplicates']],
            ['already_private', $summary['already_private']],
            ['missing', $summary['missing']],
        ]);

        $this->info('Legacy media migration completed.');

        return self::SUCCESS;
    }
}
