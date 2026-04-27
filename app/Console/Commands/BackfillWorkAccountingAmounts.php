<?php

namespace App\Console\Commands;

use App\Models\CompanyWorkPhaseRate;
use App\Models\Work;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class BackfillWorkAccountingAmounts extends Command
{
    protected $signature = 'works:backfill-accounting-amounts
        {--chunk=500 : Number of work rows to process per chunk}
        {--apply : Persist accounting amount updates}
        {--force : Skip production confirmation}
        {--include-trashed : Include soft-deleted works}';

    protected $description = 'Backfill missing work accounting amounts using current company work phase rates.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($apply && app()->environment('production') && ! $force) {
            $confirmed = $this->confirm(
                'You are about to update missing works.unit_rate and works.accounting_amount in production using current rates. Continue?'
            );

            if (! $confirmed) {
                $this->warn('Aborted.');

                return self::FAILURE;
            }
        }

        $rates = CompanyWorkPhaseRate::query()
            ->get(['company_id', 'work_phase_id', 'unit_price'])
            ->mapWithKeys(fn (CompanyWorkPhaseRate $rate) => [
                $this->rateKey($rate->company_id, $rate->work_phase_id) => $rate->unit_price,
            ]);

        $summary = [
            'scanned' => 0,
            'eligible' => 0,
            'updated' => 0,
            'already_set' => 0,
            'missing_company' => 0,
            'missing_work_phase' => 0,
            'missing_nroe' => 0,
            'missing_rate' => 0,
            'skipped' => 0,
        ];

        $this->baseQuery()
            ->orderBy('id')
            ->chunkById($chunkSize, function ($works) use ($apply, $rates, &$summary): void {
                $updates = [];

                foreach ($works as $work) {
                    $summary['scanned']++;

                    if ($work->unit_rate !== null && $work->accounting_amount !== null) {
                        $summary['already_set']++;
                        continue;
                    }

                    if (! $work->company_id) {
                        $summary['missing_company']++;
                        $summary['skipped']++;
                        continue;
                    }

                    if (! $work->work_phase_id) {
                        $summary['missing_work_phase']++;
                        $summary['skipped']++;
                        continue;
                    }

                    $quantity = $this->accountingQuantity($work->nroe);

                    if ($quantity === null) {
                        $summary['missing_nroe']++;
                        $summary['skipped']++;
                        continue;
                    }

                    $unitRate = $rates->get($this->rateKey($work->company_id, $work->work_phase_id));

                    if ($unitRate === null) {
                        $summary['missing_rate']++;
                        $summary['skipped']++;
                        continue;
                    }

                    $summary['eligible']++;
                    $updates[$work->id] = [
                        'unit_rate' => $unitRate,
                        'accounting_amount' => round((float) $unitRate * $quantity, 2),
                    ];
                }

                if (! $apply || $updates === []) {
                    return;
                }

                DB::transaction(function () use ($updates, &$summary): void {
                    foreach ($updates as $workId => $values) {
                        $updateQuery = (bool) $this->option('include-trashed')
                            ? Work::withTrashed()
                            : Work::query();

                        $updated = $updateQuery
                            ->whereKey($workId)
                            ->where(function (Builder $query): void {
                                $query->whereNull('unit_rate')
                                    ->orWhereNull('accounting_amount');
                            })
                            ->update($values);

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

        return self::SUCCESS;
    }

    private function baseQuery(): Builder
    {
        $query = (bool) $this->option('include-trashed')
            ? Work::withTrashed()
            : Work::query();

        return $query
            ->where(function (Builder $query): void {
                $query->whereNull('unit_rate')
                    ->orWhereNull('accounting_amount');
            })
            ->select(['id', 'company_id', 'work_phase_id', 'nroe', 'unit_rate', 'accounting_amount']);
    }

    private function rateKey(int|string $companyId, int|string $workPhaseId): string
    {
        return "{$companyId}:{$workPhaseId}";
    }

    private function accountingQuantity(mixed $nroe): ?float
    {
        if ($nroe === null) {
            return 1.0;
        }

        return is_numeric($nroe) ? (float) $nroe : null;
    }
}
