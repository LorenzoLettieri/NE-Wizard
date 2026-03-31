<?php

namespace App\Livewire\Concerns;

use App\Models\Work;
use App\Models\WorkSuspension;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

trait HandlesWorkSuspensions
{
    public array $suspensions = [];

    protected function loadStructuredSuspensions(Work $work): void
    {
        if (! $this->canManageStructuredSuspensions()) {
            $this->suspensions = [];

            return;
        }

        $this->suspensions = $work->workSuspensions()
            ->orderBy('started_at')
            ->get()
            ->map(fn (WorkSuspension $suspension): array => [
                'id' => $suspension->id,
                'started_at' => $this->formatSuspensionForInput($suspension->started_at),
                'ended_at' => $this->formatSuspensionForInput($suspension->ended_at),
            ])
            ->all();
    }

    public function addSuspension(): void
    {
        if (! $this->canManageStructuredSuspensions()) {
            return;
        }

        $this->suspensions[] = [
            'id' => null,
            'started_at' => '',
            'ended_at' => '',
        ];
    }

    public function removeSuspension(int $index): void
    {
        if (! $this->canManageStructuredSuspensions() || ! array_key_exists($index, $this->suspensions)) {
            return;
        }

        unset($this->suspensions[$index]);

        $this->suspensions = array_values($this->suspensions);
    }

    protected function canManageStructuredSuspensions(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'supervisor']);
    }

    /**
     * @return array<int, array{id:int|null, started_at:Carbon, ended_at:Carbon|null}>
     */
    protected function validateStructuredSuspensions(Work $work): array
    {
        if (! $this->canManageStructuredSuspensions()) {
            return [];
        }

        $validator = Validator::make(
            ['suspensions' => $this->suspensions],
            [
                'suspensions' => ['array'],
                'suspensions.*.id' => ['nullable', 'integer'],
                'suspensions.*.started_at' => ['required', 'date_format:Y-m-d\\TH:i'],
                'suspensions.*.ended_at' => ['nullable', 'date_format:Y-m-d\\TH:i'],
            ],
            [
                'suspensions.*.started_at.required' => 'La data di inizio sospensione è obbligatoria.',
                'suspensions.*.started_at.date_format' => 'La data di inizio sospensione non è valida.',
                'suspensions.*.ended_at.date_format' => 'La data di fine sospensione non è valida.',
            ]
        );

        $normalizedSuspensions = [];
        $existingIds = $work->workSuspensions()->pluck('id')->map(fn (int $id): int => $id)->all();

        $validator->after(function ($validator) use (&$normalizedSuspensions, $existingIds): void {
            $openSuspensionIndices = [];

            foreach ($this->suspensions as $index => $row) {
                $id = isset($row['id']) && $row['id'] !== '' ? (int) $row['id'] : null;

                if ($id !== null && ! in_array($id, $existingIds, true)) {
                    $validator->errors()->add("suspensions.{$index}.started_at", 'La sospensione selezionata non appartiene a questa lavorazione.');
                    continue;
                }

                $startedAt = $this->parseSuspensionInput($row['started_at'] ?? null);
                $endedAt = $this->parseSuspensionInput($row['ended_at'] ?? null, true);

                if (! $startedAt) {
                    continue;
                }

                if ($endedAt && $endedAt->lt($startedAt)) {
                    $validator->errors()->add("suspensions.{$index}.ended_at", 'La fine sospensione deve essere successiva all\'inizio.');
                    continue;
                }

                if (! $endedAt) {
                    $openSuspensionIndices[] = $index;
                }

                $normalizedSuspensions[] = [
                    'id' => $id,
                    'index' => $index,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                ];
            }

            if (count($openSuspensionIndices) > 1) {
                foreach (array_slice($openSuspensionIndices, 1) as $index) {
                    $validator->errors()->add("suspensions.{$index}.ended_at", 'Puoi avere una sola sospensione aperta per lavorazione.');
                }
            }

            $sortedSuspensions = collect($normalizedSuspensions)
                ->sortBy(fn (array $row) => $row['started_at']->getTimestamp())
                ->values();

            $previous = null;

            foreach ($sortedSuspensions as $current) {
                if ($previous === null) {
                    $previous = $current;
                    continue;
                }

                $previousEnd = $previous['ended_at'];

                if ($previousEnd === null || $current['started_at']->lt($previousEnd)) {
                    $validator->errors()->add(
                        "suspensions.{$current['index']}.started_at",
                        'Gli intervalli di sospensione non possono sovrapporsi.'
                    );
                }

                if ($previousEnd === null || ($current['ended_at'] && $previousEnd->lt($current['ended_at']))) {
                    $previous = $current;
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return collect($normalizedSuspensions)
            ->sortBy(fn (array $row) => $row['started_at']->getTimestamp())
            ->map(fn (array $row): array => [
                'id' => $row['id'],
                'started_at' => $row['started_at'],
                'ended_at' => $row['ended_at'],
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{id:int|null, started_at:Carbon, ended_at:Carbon|null}>  $normalizedSuspensions
     */
    protected function syncStructuredSuspensions(Work $work, array $normalizedSuspensions): void
    {
        if (! $this->canManageStructuredSuspensions()) {
            return;
        }

        $existingSuspensions = $work->workSuspensions()->get()->keyBy('id');
        $keptIds = collect($normalizedSuspensions)
            ->pluck('id')
            ->filter()
            ->map(fn (int $id): int => $id)
            ->all();

        if (! empty($keptIds)) {
            $work->workSuspensions()->whereNotIn('id', $keptIds)->delete();
        } else {
            $work->workSuspensions()->delete();
        }

        foreach ($normalizedSuspensions as $payload) {
            /** @var WorkSuspension $suspension */
            $suspension = $payload['id']
                ? $existingSuspensions->get($payload['id'])
                : new WorkSuspension(['work_id' => $work->id]);

            $suspension->fill([
                'started_at' => $payload['started_at'],
                'ended_at' => $payload['ended_at'],
            ]);
            $suspension->work()->associate($work);
            $suspension->save();
        }

        $work->unsetRelation('workSuspensions');
        $this->loadStructuredSuspensions($work->fresh('workSuspensions'));
    }

    protected function formatSuspensionForInput(?CarbonInterface $dateTime): string
    {
        return $dateTime?->copy()->timezone('Europe/Rome')->format('Y-m-d\\TH:i') ?? '';
    }

    protected function parseSuspensionInput(?string $value, bool $nullable = false): ?Carbon
    {
        if ($nullable && blank($value)) {
            return null;
        }

        if (blank($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d\\TH:i', $value, 'Europe/Rome')->utc();
        } catch (\Throwable) {
            return null;
        }
    }
}
