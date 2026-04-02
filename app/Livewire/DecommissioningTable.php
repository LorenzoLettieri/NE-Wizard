<?php

namespace App\Livewire;

use App\Models\Central;
use App\Models\Comune;
use App\Models\Decommissioning;
use App\Models\Regione;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;

class DecommissioningTable extends DataTableComponent
{
    public function builder(): Builder
    {
        return Decommissioning::query()
            ->with(['central', 'comune', 'regione', 'progettista'])
            ->withCount('media');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50, 100]);
        $this->setTableWrapperAttributes(['class' => 'table-sticky-head-wrapper']);
        $this->setTableAttributes(['class' => 'table-hover']);
        $this->setTheadAttributes(['class' => 'table-sticky-head']);
        $this->setSecondaryHeaderStatus(true);
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Data Inserimento', 'created_at')
                ->config(['locale' => 'it'])
                ->filter(fn (Builder $builder, array $range) => $builder
                    ->whereDate('decommissionings.created_at', '>=', $range['minDate'])
                    ->whereDate('decommissionings.created_at', '<=', $range['maxDate'])),

            SelectFilter::make('Stato', 'status')
                ->options([
                    '' => 'Tutti',
                    'Da Lavorare' => 'Da Lavorare',
                    'In Lavorazione' => 'In Lavorazione',
                    'Fine Lavori' => 'Fine Lavori',
                ])
                ->filter(fn (Builder $builder, string $value) => $builder->where('status', $value)),

            SelectFilter::make('Centrale', 'central_id')
                ->options(['' => 'Tutte'] + Central::orderBy('central')->pluck('central', 'id')->toArray())
                ->filter(fn (Builder $builder, string $value) => $builder->where('central_id', $value)),

            SelectFilter::make('Comune', 'comune_id')
                ->options(['' => 'Tutti'] + Comune::orderBy('name')->pluck('name', 'id')->toArray())
                ->filter(fn (Builder $builder, string $value) => $builder->where('comune_id', $value)),

            SelectFilter::make('Regione', 'regione_id')
                ->options(['' => 'Tutte'] + Regione::orderBy('nome')->pluck('nome', 'id')->toArray())
                ->filter(fn (Builder $builder, string $value) => $builder->where('regione_id', $value)),

            SelectFilter::make('Progettista', 'progettista_id')
                ->options(['' => 'Tutti'] + User::role('Deco')->orderBy('name')->pluck('name', 'id')->toArray())
                ->filter(fn (Builder $builder, string $value) => $builder->where('progettista_id', $value)),

            DateRangeFilter::make('FL Permesso', 'fl_permesso')
                ->config(['locale' => 'it'])
                ->filter(fn (Builder $builder, array $range) => $builder
                    ->whereDate('fl_permesso', '>=', $range['minDate'])
                    ->whereDate('fl_permesso', '<=', $range['maxDate'])),

            DateRangeFilter::make('Data IL', 'data_il')
                ->config(['locale' => 'it'])
                ->filter(fn (Builder $builder, array $range) => $builder
                    ->whereDate('data_il', '>=', $range['minDate'])
                    ->whereDate('data_il', '<=', $range['maxDate'])),

            DateRangeFilter::make('Data FL', 'data_fl')
                ->config(['locale' => 'it'])
                ->filter(fn (Builder $builder, array $range) => $builder
                    ->whereDate('data_fl', '>=', $range['minDate'])
                    ->whereDate('data_fl', '<=', $range['maxDate'])),

            TextFilter::make('Permesso Ente', 'permesso_ente')
                ->filter(fn (Builder $builder, string $value) => $builder->where('permesso_ente', 'like', "%{$value}%")),

            TextFilter::make('Q1', 'qty_1')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_1', $value)),

            TextFilter::make('Q2', 'qty_2')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_2', $value)),

            TextFilter::make('Q3', 'qty_3')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_3', $value)),

            TextFilter::make('Q4', 'qty_4')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_4', $value)),

            TextFilter::make('Q5', 'qty_5')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_5', $value)),

            TextFilter::make('Q6', 'qty_6')
                ->filter(fn (Builder $builder, string $value) => $builder->where('qty_6', $value)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Data Inserimento', 'created_at')
                ->format(fn ($value) => Carbon::parse($value)->setTimezone('Europe/Rome')->format('d/m/Y'))
                ->sortable()
                ->secondaryHeaderFilter('created_at'),

            Column::make('Stato', 'status')
                ->format(function ($value) {
                    return match ($value) {
                        'In Lavorazione' => "<span class='badge rounded-pill text-bg-primary'>{$value}</span>",
                        'Fine Lavori' => "<span class='badge rounded-pill text-bg-success'>{$value}</span>",
                        default => "<span class='badge rounded-pill text-bg-secondary'>{$value}</span>",
                    };
                })
                ->html()
                ->sortable()
                ->secondaryHeaderFilter('status'),

            Column::make('Centrale', 'central.central')
                ->sortable()
                ->searchable()
                ->secondaryHeaderFilter('central_id'),

            Column::make('Comune', 'comune.name')
                ->sortable()
                ->searchable()
                ->secondaryHeaderFilter('comune_id'),

            Column::make('Regione', 'regione.nome')
                ->sortable()
                ->searchable()
                ->secondaryHeaderFilter('regione_id'),

            Column::make('Permesso Ente', 'permesso_ente')
                ->sortable()
                ->searchable()
                ->secondaryHeaderFilter('permesso_ente'),

            Column::make('FL Permesso', 'fl_permesso')
                ->format(fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '-')
                ->sortable()
                ->secondaryHeaderFilter('fl_permesso'),

            Column::make('Progettista', 'progettista.name')
                ->sortable()
                ->searchable()
                ->secondaryHeaderFilter('progettista_id'),

            Column::make('Data IL', 'data_il')
                ->format(fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '-')
                ->sortable()
                ->secondaryHeaderFilter('data_il'),

            Column::make('Data FL', 'data_fl')
                ->format(fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '-')
                ->sortable()
                ->secondaryHeaderFilter('data_fl'),

            Column::make('Q1', 'qty_1')->sortable()->secondaryHeaderFilter('qty_1'),
            Column::make('Q2', 'qty_2')->sortable()->secondaryHeaderFilter('qty_2'),
            Column::make('Q3', 'qty_3')->sortable()->secondaryHeaderFilter('qty_3'),
            Column::make('Q4', 'qty_4')->sortable()->secondaryHeaderFilter('qty_4'),
            Column::make('Q5', 'qty_5')->sortable()->secondaryHeaderFilter('qty_5'),
            Column::make('Q6', 'qty_6')->sortable()->secondaryHeaderFilter('qty_6'),

            Column::make('Allegati')
                ->label(fn ($row) => ($row->media_count ?? 0) > 0
                    ? '<span class="badge rounded-pill text-bg-success" title="Allegati presenti"><i class="bi bi-check-lg"></i></span>'
                    : '<span class="badge rounded-pill text-bg-danger" title="Nessun allegato"><i class="bi bi-x-lg"></i></span>')
                ->html(),

            Column::make('Tot Prog', 'tot_prog')
                ->format(fn ($value) => number_format((float) $value, 2, ',', '.') . ' €')
                ->hideIf(! Auth::user()->hasRole('admin')),

            Column::make('Tot NE', 'tot_ne')
                ->format(fn ($value) => number_format((float) $value, 2, ',', '.') . ' €')
                ->hideIf(! Auth::user()->hasRole('admin')),

            Column::make('Agio', 'agio')
                ->format(fn ($value) => number_format((float) $value, 2, ',', '.') . ' €')
                ->hideIf(! Auth::user()->hasRole('admin')),

            Column::make('Pagata Prog', 'pagata_prog')
                ->format(fn ($value) => $value ? 'SI' : 'NO')
                ->hideIf(! Auth::user()->hasRole('admin')),

            Column::make('Pagata NE', 'pagata_ne')
                ->format(fn ($value) => $value ? 'SI' : 'NO')
                ->hideIf(! Auth::user()->hasRole('admin')),

            Column::make('Azioni')
                ->label(fn ($row) => view('decommissionings.decommissionings-table-actions', ['row' => $row]))
                ->html(),
        ];
    }
}
