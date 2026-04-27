<?php

namespace App\Console\Commands;

use App\Models\NetworkScope;
use App\Models\Work;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SyncNetworkScopes extends Command
{
    protected $signature = 'works:sync-network-scopes
        {--chunk=500 : Number of work rows to process per chunk}
        {--apply : Persist network_scope_id updates}
        {--force : Skip production confirmation}
        {--include-trashed : Include soft-deleted works}
        {--only-missing : Do not overwrite existing network_scope_id values}';

    protected $description = 'Map legacy works.ntw_scope values to the normalized network_scope_id foreign key.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $onlyMissing = (bool) $this->option('only-missing');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($apply && app()->environment('production') && ! $force) {
            $confirmed = $this->confirm(
                'You are about to update works.network_scope_id in production. Continue?'
            );

            if (! $confirmed) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $scopeIdsByName = NetworkScope::query()->pluck('id', 'name')->all();
        $summary = [
            'matched' => 0,
            'updated' => 0,
            'already_set' => 0,
            'empty_scope' => 0,
            'unknown_scope' => 0,
            'skipped' => 0,
        ];
        $unknownValues = [];
        $matchedValues = [];

        $this->baseQuery($onlyMissing)
            ->orderBy('id')
            ->chunkById($chunkSize, function ($works) use (
                $apply,
                $onlyMissing,
                $scopeIdsByName,
                &$summary,
                &$unknownValues,
                &$matchedValues
            ): void {
                $updates = [];

                foreach ($works as $work) {
                    $rawScope = trim((string) $work->ntw_scope);

                    if ($rawScope === '') {
                        $summary['empty_scope']++;
                        $summary['skipped']++;
                        continue;
                    }

                    $scopeId = $scopeIdsByName[$rawScope] ?? null;

                    if (! $scopeId) {
                        $summary['unknown_scope']++;
                        $summary['skipped']++;
                        $unknownValues[$rawScope] = ($unknownValues[$rawScope] ?? 0) + 1;
                        continue;
                    }

                    $summary['matched']++;
                    $matchedValues[$rawScope] = $rawScope;

                    if ((int) $work->network_scope_id === (int) $scopeId) {
                        $summary['already_set']++;
                        continue;
                    }

                    if ($onlyMissing && $work->network_scope_id !== null) {
                        $summary['skipped']++;
                        continue;
                    }

                    $updates[$work->id] = $scopeId;
                }

                if (! $apply || $updates === []) {
                    return;
                }

                DB::transaction(function () use ($updates, &$summary): void {
                    foreach ($updates as $workId => $scopeId) {
                        $updateQuery = (bool) $this->option('include-trashed')
                            ? Work::withTrashed()
                            : Work::query();

                        $updated = $updateQuery
                            ->whereKey($workId)
                            ->update(['network_scope_id' => $scopeId]);

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
            foreach ($matchedValues as $value) {
                $this->line(" - {$value}");
            }
        }

        if ($unknownValues !== []) {
            $this->warn('Unknown ntw_scope values were skipped:');
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
            $query->whereNull('network_scope_id');
        }

        return $query->select(['id', 'ntw_scope', 'network_scope_id']);
    }
}
