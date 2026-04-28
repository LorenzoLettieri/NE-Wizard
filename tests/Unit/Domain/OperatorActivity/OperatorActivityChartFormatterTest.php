<?php

namespace Tests\Unit\Domain\OperatorActivity;

use App\Domain\OperatorActivity\OperatorActivityChartFormatter;
use App\Domain\OperatorActivity\OperatorActivityCollection;
use App\Domain\OperatorActivity\OperatorActivityInterval;
use App\Domain\OperatorActivity\OperatorActivitySummary;
use App\Models\User;
use Carbon\Carbon;
use Tests\TestCase;

class OperatorActivityChartFormatterTest extends TestCase
{
    public function test_formatter_clips_daily_view_and_exposes_shift_pause_events(): void
    {
        $operator = User::factory()->make(['id' => 15, 'name' => 'Anna Verdi']);

        $summary = OperatorActivitySummary::fromCollections(
            presence: new OperatorActivityCollection([
                new OperatorActivityInterval(
                    'presence',
                    'Turno',
                    Carbon::parse('2026-04-10 06:30:00', 'UTC'),
                    Carbon::parse('2026-04-10 20:00:00', 'UTC')
                ),
            ]),
            breaks: new OperatorActivityCollection(),
            activeWork: new OperatorActivityCollection([
                new OperatorActivityInterval(
                    'active_work',
                    'Lavoro: WO-15',
                    Carbon::parse('2026-04-10 06:30:00', 'UTC'),
                    Carbon::parse('2026-04-10 20:00:00', 'UTC'),
                    ['work_label' => 'WO-15']
                ),
            ]),
            suspensions: new OperatorActivityCollection(),
            overtime: new OperatorActivityCollection(),
            leaves: new OperatorActivityCollection(),
            rawWork: new OperatorActivityCollection(),
            events: new OperatorActivityCollection([
                new OperatorActivityInterval(
                    'shift_start_marker',
                    'Ingresso turno',
                    Carbon::parse('2026-04-10 07:05:00', 'UTC'),
                    Carbon::parse('2026-04-10 07:10:00', 'UTC'),
                    ['event_label' => 'Ingresso turno']
                ),
                new OperatorActivityInterval(
                    'break_start_marker',
                    'Inizio pausa',
                    Carbon::parse('2026-04-10 11:00:00', 'UTC'),
                    Carbon::parse('2026-04-10 11:05:00', 'UTC'),
                    ['event_label' => 'Inizio pausa']
                ),
                new OperatorActivityInterval(
                    'break_end_marker',
                    'Fine pausa',
                    Carbon::parse('2026-04-10 11:30:00', 'UTC'),
                    Carbon::parse('2026-04-10 11:35:00', 'UTC'),
                    ['event_label' => 'Fine pausa']
                ),
                new OperatorActivityInterval(
                    'shift_end_marker',
                    'Uscita turno',
                    Carbon::parse('2026-04-10 18:55:00', 'UTC'),
                    Carbon::parse('2026-04-10 19:00:00', 'UTC'),
                    ['event_label' => 'Uscita turno']
                ),
            ]),
        );

        $series = app(OperatorActivityChartFormatter::class)->forOperator(
            $operator,
            $summary,
            Carbon::parse('2026-04-10 07:00:00', 'UTC'),
            Carbon::parse('2026-04-10 19:00:00', 'UTC'),
        );

        $presenceSeries = collect($series)->firstWhere('name', 'Presenza');
        $activeWorkSeries = collect($series)->firstWhere('name', 'Lavoro attivo');
        $entrySeries = collect($series)->firstWhere('name', 'Ingresso turno');
        $breakStartSeries = collect($series)->firstWhere('name', 'Inizio pausa');

        $this->assertNotNull($presenceSeries);
        $this->assertNotNull($activeWorkSeries);
        $this->assertNotNull($entrySeries);
        $this->assertNotNull($breakStartSeries);
        $this->assertSame('#0d6efd', $presenceSeries['color']);
        $this->assertSame('#198754', $activeWorkSeries['color']);
        $this->assertTrue($entrySeries['data'][0]['meta']['is_event']);
        $this->assertSame(
            Carbon::parse('2026-04-10 07:00:00', 'UTC')->getTimestamp() * 1000,
            $presenceSeries['data'][0]['y'][0]
        );
        $this->assertSame(
            Carbon::parse('2026-04-10 19:00:00', 'UTC')->getTimestamp() * 1000,
            $activeWorkSeries['data'][0]['y'][1]
        );
        $this->assertSame('Ingresso turno', $entrySeries['data'][0]['meta']['event_label']);
        $this->assertSame('Inizio pausa', $breakStartSeries['data'][0]['meta']['event_label']);
    }
}
