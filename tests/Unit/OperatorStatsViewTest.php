<?php

namespace Tests\Unit;

use Carbon\Carbon;
use ReflectionClass;
use Tests\TestCase;

class OperatorStatsViewTest extends TestCase
{
    public function test_operator_stats_chart_enables_zoom_pan_and_selection_with_toolbar(): void
    {
        // Lo zoom è stato reintrodotto di proposito (commit "zoom reintagrated in charts")
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString("autoSelected: 'pan'", $html);
        $this->assertStringContainsString("selection: { enabled: true }", $html);
        $this->assertStringContainsString("zoom: { enabled: true, allowMouseWheelZoom: true }", $html);
        $this->assertStringContainsString("pan: { enabled: true }", $html);
    }

    public function test_operator_stats_chart_uses_cursor_based_tooltip_positioning(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString("followCursor: true", $html);
        $this->assertStringContainsString("intersect: false", $html);
    }

    public function test_operator_stats_chart_forces_rerender_on_theme_change(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString("function renderChart(payload, force = false)", $html);
        $this->assertStringContainsString("if (!force && serialized === lastPayload && chart)", $html);
        $this->assertStringContainsString("window.addEventListener('timeline-data'", $html);
        $this->assertStringContainsString("renderChart(JSON.parse(lastPayload), true)", $html);
    }

    public function test_operator_stats_view_exposes_chart_mode_and_period_selectors(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString('wire:model.live="viewMode"', $html);
        $this->assertStringContainsString('wire:model.live="selectedDay"', $html);
        $this->assertStringContainsString('wire:model.live="selectedWeekStart"', $html);
    }

    public function test_operator_stats_chart_legend_excludes_suspension_from_timeline(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringNotContainsString('background:#ffc107', $html);
    }

    public function test_operator_stats_does_not_cache_timeline_rows(): void
    {
        $source = file_get_contents(app_path('Livewire/OperatorStats.php'));

        $this->assertStringNotContainsString('Cache::remember', $source);
        $this->assertStringNotContainsString('operator_stats_batch', $source);
    }

    public function test_daily_timeline_window_uses_rome_business_hours(): void
    {
        $component = new \App\Livewire\OperatorStats();
        $component->viewMode = 'day';
        $component->selectedDay = '2026-04-10';

        $method = (new ReflectionClass(\App\Livewire\OperatorStats::class))->getMethod('resolveTimelineWindow');
        $method->setAccessible(true);

        [$start, $end] = $method->invoke(
            $component,
            Carbon::parse('2026-04-01 00:00:00', 'Europe/Rome'),
            Carbon::parse('2026-04-30 23:59:59', 'Europe/Rome'),
        );

        $this->assertSame('2026-04-10 07:00', $start->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'));
        $this->assertSame('2026-04-10 19:00', $end->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'));
        $this->assertSame('UTC', $start->timezoneName);
        $this->assertSame('UTC', $end->timezoneName);
    }

    public function test_operator_stats_chart_formats_axis_in_local_time(): void
    {
        // Dal commit "timezone fixes" l'asse usa l'ora locale del browser (datetimeUTC: false)
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString('datetimeUTC: false', $html);
        $this->assertStringNotContainsString('formatRomeTimeLabel', $html);
    }

    public function test_operator_stats_filter_range_uses_rome_day_boundaries(): void
    {
        $component = new \App\Livewire\OperatorStats();

        $method = (new ReflectionClass(\App\Livewire\OperatorStats::class))->getMethod('resolveReportRange');
        $method->setAccessible(true);

        [$start, $end] = $method->invoke($component, '2026-04-10', '2026-04-10');

        $this->assertSame('2026-04-10 00:00', $start->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'));
        $this->assertSame('2026-04-10 23:59', $end->copy()->timezone('Europe/Rome')->format('Y-m-d H:i'));
        $this->assertSame('UTC', $start->timezoneName);
        $this->assertSame('UTC', $end->timezoneName);
    }
}
