<?php

namespace App\Console\Commands;

use App\Models\Work;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PatchLazioAggiornamentoPhase extends Command
{
    protected $signature = 'works:patch-lazio-aggiornamento-phase
        {--apply : Persist the work_phase_id change (omit for dry-run)}
        {--force : Skip the production confirmation prompt}';

    protected $description = 'Set work_phase_id = 11 on AGGIORNAMENTO works (phase 3) whose central is in LAZIO with network_scope_id 1 or 2.';

    private const SOURCE_PHASE = 3;
    private const TARGET_PHASE = 11;
    private const REGION        = 'LAZIO';
    private const SCOPES        = [1, 2];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');

        if ($apply && app()->environment('production') && ! $force) {
            $confirmed = $this->confirm(
                sprintf(
                    '[PRODUCTION] This will set work_phase_id = %d on all matching works (currently phase %d, region %s, scopes %s). Continue?',
                    self::TARGET_PHASE,
                    self::SOURCE_PHASE,
                    self::REGION,
                    implode('/', self::SCOPES),
                )
            );

            if (! $confirmed) {
                $this->warn('Aborted.');
                return self::FAILURE;
            }
        }

        $ids = $this->matchingIds();
        $count = count($ids);

        if ($count === 0) {
            $this->info('No matching works found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Matching works: %d', $count));
        $this->table(['work_id'], collect($ids)->map(fn (int $id) => [$id])->all());

        if (! $apply) {
            $this->warn('Dry-run — no rows were updated. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        $updated = 0;

        DB::transaction(function () use ($ids, &$updated): void {
            // Re-query inside the transaction with a lock so nothing races us.
            $updated = Work::query()
                ->whereIn('id', $ids)
                ->where('work_phase_id', self::SOURCE_PHASE)
                ->lockForUpdate()
                ->update(['work_phase_id' => self::TARGET_PHASE]);
        });

        $this->info(sprintf('Done. Rows updated: %d', $updated));

        if ($updated !== $count) {
            $this->warn(sprintf(
                '%d row(s) were matched in the preview but not updated (may have been modified concurrently or already had a different phase).',
                $count - $updated
            ));
        }

        return self::SUCCESS;
    }

    /** @return int[] */
    private function matchingIds(): array
    {
        return Work::query()
            ->whereHas('central', function (Builder $q): void {
                $q->where('region', self::REGION);
            })
            ->where('work_phase_id', self::SOURCE_PHASE)
            ->whereIn('network_scope_id', self::SCOPES)
            ->orderBy('id')
            ->pluck('id')
            ->all();
    }
}
