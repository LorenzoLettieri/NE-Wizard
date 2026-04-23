<?php

namespace App\Console\Commands;

use App\Models\Work;
use App\Models\WorkPhase;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SyncWorkPhases extends Command
{
    protected $signature = 'works:sync-phases
        {--chunk=500 : Number of work rows to process per chunk}
        {--apply : Persist work_phase_id updates}
        {--force : Skip production confirmation}
        {--include-trashed : Include soft-deleted works}
        {--only-missing : Do not overwrite existing work_phase_id values}';

    protected $description = 'Map legacy works.phase values to the normalized work_phase_id foreign key.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $onlyMissing = (bool) $this->option('only-missing');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($apply && app()->environment('production') && ! $force) {
            $confirmed = $this->confirm(
                'You are about to update works.work_phase_id in production. Continue?'
            );

            if (! $confirmed) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $phaseIdsByName = WorkPhase::query()->pluck('id', 'name')->all();
        $summary = [
            'matched' => 0,
            'updated' => 0,
            'already_set' => 0,
            'empty_phase' => 0,
            'unknown_phase' => 0,
            'skipped' => 0,
        ];
        $unknownValues = [];
        $matchedValues = [];

        $this->baseQuery($onlyMissing)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($works) use (
                $apply,
                $onlyMissing,
                $phaseIdsByName,
                &$summary,
                &$unknownValues,
                &$matchedValues
            ): void {
                $updates = [];

                foreach ($works as $work) {
                    $legacyPhase = trim((string) $work->phase);
                    $canonicalName = WorkPhase::normalizeLegacyName($legacyPhase);

                    if ($canonicalName === null) {
                        $summary['empty_phase']++;
                        $summary['skipped']++;
                        continue;
                    }

                    $phaseId = $phaseIdsByName[$canonicalName] ?? null;

                    if (! $phaseId) {
                        $summary['unknown_phase']++;
                        $summary['skipped']++;
                        $unknownValues[$legacyPhase] = ($unknownValues[$legacyPhase] ?? 0) + 1;
                        continue;
                    }

                    $summary['matched']++;
                    $matchedValues[$legacyPhase] = $canonicalName;

                    if ((int) $work->work_phase_id === (int) $phaseId) {
                        $summary['already_set']++;
                        continue;
                    }

                    if ($onlyMissing && $work->work_phase_id !== null) {
                        $summary['skipped']++;
                        continue;
                    }

                    $updates[$work->id] = $phaseId;
                }

                if (! $apply || $updates === []) {
                    return;
                }

                DB::transaction(function () use ($updates, &$summary): void {
                    foreach ($updates as $workId => $phaseId) {
                        $updateQuery = (bool) $this->option('include-trashed')
                            ? Work::withTrashed()
                            : Work::query();

                        $updated = $updateQuery
                            ->whereKey($workId)
                            ->update(['work_phase_id' => $phaseId]);

                        if ($updated > 0) {
                            $summary['updated']++;
                        }
                    }
                });
            });

        $this->line($apply ? 'Apply mode completed.' : 'Dry-run completed. No rows were updated.');
        $this->table(['Outcome', 'Count'], collect($summary)
            ->map(fn (int $count, string $label) => [$label, $count])
            ->values()
            ->all());

        if ($matchedValues !== []) {
            $this->line('Matched values:');
            foreach ($matchedValues as $legacy => $canonical) {
                $this->line(" - {$legacy} => {$canonical}");
            }
        }

        if ($unknownValues !== []) {
            $this->warn('Unknown phase values were skipped:');
            foreach ($unknownValues as $value => $count) {
                $this->line(" - {$value}: {$count}");
            }
        }

        return self::SUCCESS;
    }

    private function baseQuery(bool $onlyMissing): Builder
    {
        $query = (bool) $this->option('include-trashed')
            ? Work::withTrashed()
            : Work::query();

        if ($onlyMissing) {
            $query->whereNull('work_phase_id');
        }

        return $query->select(['id', 'phase', 'work_phase_id']);
    }
}
