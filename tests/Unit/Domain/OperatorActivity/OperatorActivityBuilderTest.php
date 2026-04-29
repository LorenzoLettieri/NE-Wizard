<?php

namespace Tests\Unit\Domain\OperatorActivity;

use App\Domain\OperatorActivity\OperatorActivityBuilder;
use App\Domain\OperatorActivity\OperatorActivityCollection;
use App\Domain\OperatorActivity\OperatorActivityInterval;
use App\Models\Timesheet;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use App\Models\WorkSuspension;
use Carbon\Carbon;
use Tests\TestCase;

class OperatorActivityBuilderTest extends TestCase
{
    public function test_collection_merges_overlapping_intervals_of_same_type(): void
    {
        $collection = new OperatorActivityCollection([
            new OperatorActivityInterval('active_work', 'WO-1', Carbon::parse('2026-04-10 08:00:00', 'UTC'), Carbon::parse('2026-04-10 10:00:00', 'UTC')),
            new OperatorActivityInterval('active_work', 'WO-1', Carbon::parse('2026-04-10 09:30:00', 'UTC'), Carbon::parse('2026-04-10 11:00:00', 'UTC')),
        ]);

        $merged = $collection->mergeOverlaps();

        $this->assertCount(1, $merged->all());
        $this->assertSame(10800, $merged->totalSecondsByType('active_work'));
    }

    public function test_collection_subtracts_breaks_from_presence_without_removing_non_overlapping_time(): void
    {
        $presence = new OperatorActivityCollection([
            new OperatorActivityInterval('presence', 'shift', Carbon::parse('2026-04-10 08:00:00', 'UTC'), Carbon::parse('2026-04-10 17:00:00', 'UTC')),
        ]);

        $breaks = new OperatorActivityCollection([
            new OperatorActivityInterval('break', 'lunch', Carbon::parse('2026-04-10 12:00:00', 'UTC'), Carbon::parse('2026-04-10 12:30:00', 'UTC')),
        ]);

        $net = $presence->subtract($breaks);

        $this->assertSame(30600, $net->totalSecondsByType('presence'));
    }

    public function test_builder_combines_work_suspensions_presence_breaks_and_overtime(): void
    {
        $operator = User::factory()->make(['id' => 99, 'name' => 'Mario Rossi']);

        $work = Work::make([
            'id' => 1,
            'wo_number' => 'WO-100',
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-04-10 08:30:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-04-10 12:30:00', 'UTC'),
        ]);

        $work->setRelation('workSuspensions', collect([
            WorkSuspension::make([
                'started_at' => Carbon::parse('2026-04-10 09:30:00', 'UTC'),
                'ended_at' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
            ]),
        ]));

        $timesheet = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'break_start' => Carbon::parse('2026-04-10 11:00:00', 'UTC'),
            'break_end' => Carbon::parse('2026-04-10 11:30:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
            'overtime_hours' => 1,
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect([$work]),
            collect([$timesheet]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-10 23:59:59', 'UTC'),
        );

        $this->assertSame(32400, $summary->presenceSeconds());
        $this->assertSame(1800, $summary->breakSeconds());
        $this->assertSame(10800, $summary->activeWorkSeconds());
        $this->assertSame(1800, $summary->suspensionSeconds());
        $this->assertSame(3600, $summary->overtimeSeconds());
    }

    public function test_builder_labels_active_work_for_chart_tooltip_with_id_scope_and_phase(): void
    {
        $operator = User::factory()->make(['id' => 99, 'name' => 'Mario Rossi']);
        $phase = WorkPhase::make(['id' => 5, 'name' => 'FASE 1']);

        $work = Work::make([
            'ntw_scope' => 'FTTH',
            'phase' => 'Legacy phase',
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-04-10 08:30:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-04-10 10:30:00', 'UTC'),
        ]);
        $work->id = 123;
        $work->setRelation('workPhase', $phase);
        $work->setRelation('workSuspensions', collect());

        $timesheet = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect([$work]),
            collect([$timesheet]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-10 23:59:59', 'UTC'),
        );

        $activeWork = $summary->activeWork->all()[0];

        $this->assertSame('Lavoro: 123 - FTTH - FASE 1', $activeWork->label);
    }

    public function test_builder_excludes_non_in_progress_works_from_active_work(): void
    {
        $operator = User::factory()->make(['id' => 88, 'name' => 'KO Operator']);

        $work = Work::make([
            'status' => 'KO',
            'acception_date' => Carbon::parse('2026-03-13 09:12:35', 'UTC'),
            'delivery_date' => null,
        ]);
        $work->id = 8447;
        $work->setRelation('workSuspensions', collect());

        $timesheet = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect([$work]),
            collect([$timesheet]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-10 23:59:59', 'UTC'),
        );

        $this->assertSame(32400, $summary->presenceSeconds());
        $this->assertSame(0, $summary->activeWorkSeconds());
        $this->assertCount(0, $summary->activeWork->all());
    }

    public function test_builder_does_not_treat_hourly_leave_start_as_open_presence(): void
    {
        $operator = User::factory()->make(['id' => 66, 'name' => 'Permesso Operator']);

        $timesheet = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 10:00:00', 'UTC'),
            'exit_time' => null,
            'leave_type' => 'permesso',
            'leave_hours' => 2,
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect(),
            collect([$timesheet]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-10 23:59:59', 'UTC'),
        );

        $this->assertSame(0, $summary->presenceSeconds());
        $this->assertSame(7200, $summary->leaveSeconds());
    }

    public function test_builder_uses_dedicated_hourly_leave_start_without_creating_presence(): void
    {
        $operator = User::factory()->make(['id' => 68, 'name' => 'Dedicated Leave Operator']);

        $timesheet = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => null,
            'exit_time' => null,
            'leave_start_time' => Carbon::parse('2026-04-10 14:00:00', 'UTC'),
            'leave_type' => 'permesso',
            'leave_hours' => 1.5,
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect(),
            collect([$timesheet]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-10 23:59:59', 'UTC'),
        );

        $leave = $summary->leaves->all()[0];

        $this->assertSame(0, $summary->presenceSeconds());
        $this->assertSame(5400, $summary->leaveSeconds());
        $this->assertSame('2026-04-10 14:00:00', $leave->start->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-10 15:30:00', $leave->end->format('Y-m-d H:i:s'));
    }

    public function test_builder_does_not_extend_unclosed_past_shift_to_end_of_report_window(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-04-12 12:00:00', 'UTC'));

        try {
            $operator = User::factory()->make(['id' => 67, 'name' => 'Open Shift Operator']);

            $timesheet = Timesheet::make([
                'date' => '2026-04-10',
                'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
                'exit_time' => null,
            ]);

            $summary = app(OperatorActivityBuilder::class)->build(
                $operator,
                collect(),
                collect([$timesheet]),
                Carbon::parse('2026-04-10 00:00:00', 'UTC'),
                Carbon::parse('2026-04-10 23:59:59', 'UTC'),
            );

            $this->assertSame(0, $summary->presenceSeconds());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_builder_projects_open_work_only_inside_presence_windows_across_shifts(): void
    {
        $operator = User::factory()->make(['id' => 77, 'name' => 'Luigi Bianchi']);

        $work = Work::make([
            'id' => 10,
            'wo_number' => 'WO-777',
            'status' => 'In Lavorazione',
            'acception_date' => Carbon::parse('2026-04-10 08:30:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-04-11 17:00:00', 'UTC'),
        ]);
        $work->setRelation('workSuspensions', collect());

        $dayOne = Timesheet::make([
            'date' => '2026-04-10',
            'entry_time' => Carbon::parse('2026-04-10 08:00:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-10 17:00:00', 'UTC'),
        ]);

        $dayTwo = Timesheet::make([
            'date' => '2026-04-11',
            'entry_time' => Carbon::parse('2026-04-11 08:00:00', 'UTC'),
            'exit_time' => Carbon::parse('2026-04-11 17:00:00', 'UTC'),
        ]);

        $summary = app(OperatorActivityBuilder::class)->build(
            $operator,
            collect([$work]),
            collect([$dayOne, $dayTwo]),
            Carbon::parse('2026-04-10 00:00:00', 'UTC'),
            Carbon::parse('2026-04-11 23:59:59', 'UTC'),
        );

        $this->assertSame(64800, $summary->presenceSeconds());
        $this->assertSame(63000, $summary->activeWorkSeconds());
    }

    public function test_overlapping_active_works_keep_their_own_chart_identity(): void
    {
        $first = new OperatorActivityCollection([
            new OperatorActivityInterval(
                'active_work',
                'Lavoro: WO-1',
                Carbon::parse('2026-04-10 08:00:00', 'UTC'),
                Carbon::parse('2026-04-10 10:00:00', 'UTC'),
                ['work_id' => 1, 'work_label' => 'WO-1'],
            ),
            new OperatorActivityInterval(
                'active_work',
                'Lavoro: WO-2',
                Carbon::parse('2026-04-10 09:00:00', 'UTC'),
                Carbon::parse('2026-04-10 11:00:00', 'UTC'),
                ['work_id' => 2, 'work_label' => 'WO-2'],
            ),
        ]);

        $merged = $first->mergeOverlaps()->all();

        $this->assertCount(2, $merged);
        $this->assertSame(['WO-1', 'WO-2'], array_map(
            fn (OperatorActivityInterval $interval): string => $interval->meta['work_label'],
            $merged,
        ));
    }
}
