<?php

namespace App\Console\Commands;

use App\Models\Work;
use App\Models\WorkStatusHistory;
use Illuminate\Console\Command;

class BackfillWorkStatusHistory extends Command
{
    protected $signature = 'works:backfill-status-history';

    protected $description = 'Ricostruisce la storia degli status per i lavori esistenti (esecuzione una-tantum)';

    public function handle(): int
    {
        $total = Work::withTrashed()->count();
        $this->info("Backfill status history per {$total} works...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        Work::withTrashed()
            ->with('workSuspensions')
            ->chunkById(200, function ($works) use ($bar) {
                $records = [];
                $now = now();

                foreach ($works as $work) {
                    // Salta se ha già record (idempotente)
                    if (WorkStatusHistory::where('work_id', $work->id)->exists()) {
                        $bar->advance();
                        continue;
                    }

                    // Fase iniziale: Da Lavorare, dalla creazione all'accettazione
                    if ($work->acception_date) {
                        $records[] = [
                            'work_id' => $work->id,
                            'status' => 'Da Lavorare',
                            'started_at' => $work->created_at ?? $work->acception_date,
                            'ended_at' => $work->acception_date,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        // Fase In Lavorazione: da acception_date in poi, spezzata dalle sospensioni
                        $this->buildInLavorazioneRecords($work, $records, $now);
                    } else {
                        // Work mai accettato: status attuale dalla creazione
                        $records[] = [
                            'work_id' => $work->id,
                            'status' => $work->status,
                            'started_at' => $work->created_at ?? $now,
                            'ended_at' => null,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $bar->advance();
                }

                if (! empty($records)) {
                    WorkStatusHistory::insert($records);
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info('Backfill completato.');

        return self::SUCCESS;
    }

    private function buildInLavorazioneRecords(Work $work, array &$records, \Carbon\Carbon $now): void
    {
        // Costruisce intervalli "In Lavorazione" sottraendo le sospensioni.
        // Le sospensioni fungono da periodi "Sospeso" intercalati.
        $suspensions = $work->workSuspensions->sortBy('started_at')->values();

        // Per i KO senza delivery_date usiamo updated_at come approssimazione del momento in cui è stato chiuso
        $workEnd = $work->delivery_date
            ?? ($work->status === 'KO' ? $work->updated_at : null);

        // Cursor indica l'inizio del prossimo tratto "In Lavorazione"
        $cursor = $work->acception_date->copy();

        foreach ($suspensions as $suspension) {
            $suspStart = $suspension->started_at;
            $suspEnd = $suspension->ended_at;

            // Tratto In Lavorazione prima della sospensione
            if ($suspStart->gt($cursor)) {
                $records[] = [
                    'work_id' => $work->id,
                    'status' => 'In Lavorazione',
                    'started_at' => $cursor,
                    'ended_at' => $suspStart,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Tratto Sospeso
            $records[] = [
                'work_id' => $work->id,
                'status' => 'Sospeso',
                'started_at' => $suspStart,
                'ended_at' => $suspEnd,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if ($suspEnd) {
                $cursor = $suspEnd->copy();
            } else {
                // Sospensione ancora aperta: nessun tratto In Lavorazione dopo
                return;
            }
        }

        // Tratto finale In Lavorazione (dopo l'ultima sospensione o dall'inizio)
        $records[] = [
            'work_id' => $work->id,
            'status' => 'In Lavorazione',
            'started_at' => $cursor,
            'ended_at' => $workEnd,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        // Se il work è consegnato/completato, aggiungi lo status finale
        if ($workEnd && in_array($work->status, ['Consegnato', 'Fine Lavori', 'KO'], true)) {
            $records[] = [
                'work_id' => $work->id,
                'status' => $work->status,
                'started_at' => $workEnd,
                'ended_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
    }
}
