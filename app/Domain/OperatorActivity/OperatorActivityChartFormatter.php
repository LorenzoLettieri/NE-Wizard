<?php

namespace App\Domain\OperatorActivity;

use App\Models\User;
use App\Models\Work;
use Carbon\Carbon;

final class OperatorActivityChartFormatter
{
    private const TYPE_CONFIG = [
        'presence' => ['name' => 'Presenza', 'color' => '#73A437'],
        'active_work' => ['name' => 'Lavorazioni', 'color' => '#faad50'],
        'break' => ['name' => 'Tempi morti', 'color' => '#466db6'],
        'leave' => ['name' => 'Permesso/Ferie', 'color' => '#dc3545'],
        'overtime' => ['name' => 'Straordinario', 'color' => '#6610f2'],
        'shift_start_marker' => ['name' => 'Ingresso turno', 'color' => '#213E73'],
        'break_start_marker' => ['name' => 'Inizio pausa', 'color' => '#213E73'],
        'break_end_marker' => ['name' => 'Fine pausa', 'color' => '#213E73'],
        'shift_end_marker' => ['name' => 'Uscita turno', 'color' => '#213E73'],
    ];

    public function forOperator(
        User $operator,
        OperatorActivitySummary $summary,
        Carbon $windowStart,
        Carbon $windowEnd,
    ): array {
        $series = [];

        foreach ($summary->intervalsForChart() as $type => $intervals) {
            if ($type === 'events') {
                foreach ($intervals as $interval) {
                    $clipped = $interval->withBounds(
                        $interval->start->copy()->max($windowStart),
                        $interval->end->copy()->min($windowEnd),
                    );

                    if (! $clipped) {
                        continue;
                    }

                    $config = self::TYPE_CONFIG[$interval->type];
                    $series[] = [
                        'name' => $config['name'],
                        'color' => $config['color'],
                        'data' => [$this->mapPoint($clipped, $config, $operator)],
                    ];
                }

                continue;
            }

            $clipped = (new OperatorActivityCollection($intervals))->clip($windowStart, $windowEnd)->all();
            if (empty($clipped)) {
                continue;
            }

            $config = self::TYPE_CONFIG[$type];
            $series[] = [
                'name' => $config['name'],
                'color' => $config['color'],
                'data' => array_map(
                    fn (OperatorActivityInterval $interval): array => $this->mapPoint($interval, $config, $operator),
                    $clipped,
                ),
            ];
        }

        return $series;
    }

    private function mapPoint(OperatorActivityInterval $interval, array $config, User $operator): array
    {
        return [
            'x' => $operator->name,
            'y' => [
                $interval->start->getTimestamp() * 1000,
                $interval->end->getTimestamp() * 1000,
            ],
            'fillColor' => $config['color'],
            'meta' => [
                'type' => $interval->type,
                'type_label' => $config['name'],
                'label' => $interval->label,
                'duration_label' => Work::formatDuration($interval->seconds()),
                'start_local' => $interval->start->copy()->timezone('Europe/Rome')->format('d/m/Y H:i'),
                'end_local' => $interval->end->copy()->timezone('Europe/Rome')->format('d/m/Y H:i'),
                'is_event' => str_ends_with($interval->type, '_marker'),
            ] + $interval->meta,
        ];
    }
}
