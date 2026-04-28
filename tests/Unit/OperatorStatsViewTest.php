<?php

namespace Tests\Unit;

use App\Livewire\OperatorStats;
use Carbon\Carbon;
use ReflectionClass;
use Tests\TestCase;

class OperatorStatsViewTest extends TestCase
{
    public function test_operator_stats_chart_disables_zoom_pan_and_selection_interactions(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString("toolbar: { show: false }", $html);
        $this->assertStringContainsString("selection: { enabled: false }", $html);
        $this->assertStringContainsString("zoom: { enabled: false, allowMouseWheelZoom: false }", $html);
        $this->assertStringContainsString("pan: { enabled: false }", $html);
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
        $this->assertStringContainsString("config: @this.get('timelineConfig')", $html);
        $this->assertStringContainsString("}, true);", $html);
    }

    public function test_operator_stats_view_exposes_chart_mode_and_period_selectors(): void
    {
        $html = file_get_contents(resource_path('views/livewire/operator-stats.blade.php'));

        $this->assertStringContainsString('wire:model.live="viewMode"', $html);
        $this->assertStringContainsString('wire:model.live="selectedDay"', $html);
        $this->assertStringContainsString('wire:model.live="selectedWeekStart"', $html);
    }

    public function test_operator_stats_exposes_chart_payload_as_livewire_state(): void
    {
        $reflection = new ReflectionClass(OperatorStats::class);

        $this->assertTrue($reflection->hasProperty('timelineData'));
        $this->assertTrue($reflection->getProperty('timelineData')->isPublic());
        $this->assertTrue($reflection->hasProperty('timelineConfig'));
        $this->assertTrue($reflection->getProperty('timelineConfig')->isPublic());
    }

    public function test_daily_timeline_window_uses_rome_business_hours(): void
    {
        $component = new OperatorStats();
        $component->viewMode = 'day';
        $component->selectedDay = '2026-04-10';

        $method = (new ReflectionClass(OperatorStats::class))->getMethod('resolveTimelineWindow');
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
}
