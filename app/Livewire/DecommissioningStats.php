<?php

namespace App\Livewire;

use App\Models\Company;
use App\Models\Decommissioning;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class DecommissioningStats extends Component
{
    private const TIMEZONE = 'Europe/Rome';
    private const COUNTED_STATUSES = [
        'in_progress_count' => 'In Lavorazione',
        'suspended_count' => 'Sospeso',
        'completed_count' => 'Fine Lavori',
    ];

    public $startDate;

    public $endDate;

    public $designerId = '';

    public $companyId = '';

    public function mount(): void
    {
        $now = Carbon::now(self::TIMEZONE);
        $this->startDate = $now->copy()->startOfMonth()->toDateString();
        $this->endDate = $now->toDateString();
    }

    public function render()
    {
        [$startDate, $endDate] = $this->resolveReportRange($this->startDate, $this->endDate);
        $designers = $this->resolveDesigners();
        $rows = $this->buildRows($designers, $startDate, $endDate);

        return view('livewire.decommissioning-stats', [
            'rows' => $rows,
            'summary' => $this->buildSummary($rows),
            'designerOptions' => User::role('Deco')->orderBy('name')->get(['id', 'name']),
            'companyOptions' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function resetFilters(): void
    {
        $this->designerId = '';
        $this->companyId = '';

        $now = Carbon::now(self::TIMEZONE);
        $this->startDate = $now->copy()->startOfMonth()->toDateString();
        $this->endDate = $now->toDateString();
    }

    private function resolveReportRange(string $startDate, string $endDate): array
    {
        return [
            Carbon::parse($startDate, self::TIMEZONE)->startOfDay()->setTimezone('UTC'),
            Carbon::parse($endDate, self::TIMEZONE)->endOfDay()->setTimezone('UTC'),
        ];
    }

    private function resolveDesigners(): Collection
    {
        return User::role('Deco')
            ->when($this->designerId !== '', fn (Builder $query) => $query->whereKey($this->designerId))
            ->orderBy('name')
            ->get();
    }

    private function buildRows(Collection $designers, Carbon $startDate, Carbon $endDate): array
    {
        if ($designers->isEmpty()) {
            return [];
        }

        $decommissionings = Decommissioning::query()
            ->whereIn('progettista_id', $designers->pluck('id')->all())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($this->companyId !== '', fn (Builder $query) => $query->where('company_id', $this->companyId))
            ->get()
            ->groupBy('progettista_id');

        return $designers
            ->map(fn (User $designer): array => $this->buildDesignerRow(
                $designer,
                $decommissionings->get($designer->id, collect()),
            ))
            ->values()
            ->all();
    }

    private function buildDesignerRow(User $designer, Collection $decommissionings): array
    {
        $completed = $decommissionings->where('status', 'Fine Lavori');

        return [
            'designer_id' => $designer->id,
            'designer_name' => $designer->name,
            ...$this->statusCounts($decommissionings),
            'paid_prog_total' => $this->sumProgTotal($completed->where('pagata_prog', true)),
            'unpaid_prog_total' => $this->sumProgTotal($completed->where('pagata_prog', false)),
        ];
    }

    private function statusCounts(Collection $decommissionings): array
    {
        $counts = [];

        foreach (self::COUNTED_STATUSES as $key => $status) {
            $counts[$key] = $decommissionings->where('status', $status)->count();
        }

        return $counts;
    }

    private function sumProgTotal(Collection $decommissionings): float
    {
        return round((float) $decommissionings->sum(fn (Decommissioning $decommissioning) => (float) ($decommissioning->tot_prog ?? 0)), 2);
    }

    private function buildSummary(array $rows): array
    {
        return [
            'in_progress_count' => array_sum(array_column($rows, 'in_progress_count')),
            'suspended_count' => array_sum(array_column($rows, 'suspended_count')),
            'completed_count' => array_sum(array_column($rows, 'completed_count')),
            'paid_prog_total' => round(array_sum(array_column($rows, 'paid_prog_total')), 2),
            'unpaid_prog_total' => round(array_sum(array_column($rows, 'unpaid_prog_total')), 2),
        ];
    }
}
