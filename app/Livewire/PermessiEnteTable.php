<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Comune;
use App\Models\Central;
use App\Models\Regione;
use Livewire\Attributes\On;
use App\Models\PermessoEnte;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class PermessiEnteTable extends DataTableComponent
{
    public function builder(): Builder
    {
        $query = PermessoEnte::query()->with(['regione', 'comune', 'central', 'users']);

        if (Auth::user()->hasAnyRole('admin', 'supervisor')) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $query) {
            $query->where('users.id', Auth::id());
        });
    }

    protected $listeners = [
        'permessoUpdated' => '$refresh',
        'take-permesso' => 'takePermesso',
        'consegna-permesso' => 'consegnaPermesso',
        'end-permesso' => 'endPermesso',
    ];

    public function takePermesso($id)
    {
        if (!Auth::user()->hasRole('permessi ente')) {
            $this->dispatch('error', 'Solo gli operatori Permessi Ente possono prendere in carico un record.');
            return;
        }

        $permesso = PermessoEnte::findOrFail($id);
        $permesso->update([
            'status' => 'In Lavorazione',
            'acception_date' => now()
        ]);
        $permesso->users()->syncWithoutDetaching([Auth::id()]);

        $this->dispatch('permessoUpdated');
    }

    public function consegnaPermesso($id)
    {
        $permesso = PermessoEnte::findOrFail($id);

        // Verifica se l'utente è assegnato
        if (!Auth::user()->hasAnyRole('admin', 'supervisor') && !$permesso->users->contains(Auth::id())) {
            $this->dispatch('error', 'Non sei assegnato a questo permesso.');
            return;
        }

        $permesso->update([
            'status' => 'Consegnato',
            'delivery_date' => now()
        ]);

        $this->dispatch('permessoUpdated');
    }

    public function endPermesso($id)
    {
        if (!Auth::user()->hasAnyRole('admin', 'supervisor')) {
            $this->dispatch('error', 'Solo admin o supervisor possono segnare come Fine Lavori.');
            return;
        }

        $permesso = PermessoEnte::findOrFail($id);
        $permesso->update([
            'status' => 'Fine Lavori',
            'completion_date' => now()
        ]);

        $this->dispatch('permessoUpdated');
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(false); // Optimized performance
        $this->setAdditionalSelects(['permessi_ente.id as id']);
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50, 100, 200, 300]);

        $this->setTableAttributes(['class' => 'table-hover']);

        $this->setTdAttributes(function (Column $column) {
            if ($column->isField('created_at') || $column->isField('consegna') || $column->isField('data_fl')) {
                return ['class' => 'text-nowrap'];
            }
            return [];
        });

        $this->setSecondaryHeaderStatus(true);
    }

    public function filters(): array
    {
        $regioni = ['' => 'Tutte'] + Regione::pluck('nome', 'id')->toArray();
        $comuni = ['' => 'Tutti'] + Comune::pluck('name', 'id')->toArray();
        $centrali = ['' => 'Tutte'] + Central::pluck('central', 'id')->toArray();

        $siNoOptions = [
            '' => '',
            1 => 'Sì',
            0 => 'No',
        ];

        return [
            DateRangeFilter::make('Data Creazione', 'created_at')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('permessi_ente.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('permessi_ente.created_at', '<=', $dateRange['maxDate']);
                }),

            TextFilter::make('Progetto', 'progetto_filter')
                ->config(['placeholder' => 'Filtra Progetto'])
                ->filter(fn(Builder $builder, string $value) => $builder->where('progetto', 'like', "%$value%")),

            SelectFilter::make('Regione', 'regione_id')
                ->options($regioni)
                ->filter(fn(Builder $builder, $value) => $builder->where('regione_id', $value)),

            SelectFilter::make('Comune', 'comune_id')
                ->options($comuni)
                ->filter(fn(Builder $builder, $value) => $builder->where('comune_id', $value)),

            SelectFilter::make('Centrale', 'centrale_id')
                ->options($centrali)
                ->filter(fn(Builder $builder, $value) => $builder->where('central_id', $value)),

            SelectFilter::make('Status', 'status_filter')
                ->options([
                        '' => 'Tutti',
                        'Da Lavorare' => 'Da Lavorare',
                        'In Lavorazione' => 'In Lavorazione',
                        'Consegnato' => 'Consegnato',
                        'Fine Lavori' => 'Fine Lavori',
                    ])->filter(fn(Builder $builder, $value) => $builder->where('status', $value)),

            TextFilter::make('Descrizione', 'descrizione_filter')
                ->config(['placeholder' => 'Cerca...'])
                ->filter(fn(Builder $builder, string $value) => $builder->where('descrizione', 'like', "%$value%")),

            TextFilter::make('Network', 'network_filter')
                ->config(['placeholder' => 'Network'])
                ->filter(fn(Builder $builder, string $value) => $builder->where('network', 'like', "%$value%")),

            TextFilter::make('Via', 'via_filter')
                ->config(['placeholder' => 'Via'])
                ->filter(fn(Builder $builder, string $value) => $builder->where('via', 'like', "%$value%")),

            DateRangeFilter::make('Consegna', 'consegna_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('consegna', '>=', $v['minDate'])->whereDate('consegna', '<=', $v['maxDate'])),

            SelectFilter::make('AP Chiusini', 'ap_chiusini_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('ap_chiusini', $value)),

            TextFilter::make('Num. Chiusini', 'num_chiusini_filter')
                ->filter(fn(Builder $builder, $value) => $builder->where('num_chiusini', $value)),

            SelectFilter::make('Scavo 100m', 'scavo_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('scavo_fino_100m', $value)),

            TextFilter::make('Quote Agg.', 'quote_filter')
                ->filter(fn(Builder $builder, $value) => $builder->where('quote_aggiuntive', $value)),

            DateRangeFilter::make('Presa in Carico', 'acception_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('acception_date', '>=', $v['minDate'])->whereDate('acception_date', '<=', $v['maxDate'])),

            DateRangeFilter::make('Consegna Op.', 'delivery_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('delivery_date', '>=', $v['minDate'])->whereDate('delivery_date', '<=', $v['maxDate'])),

            DateRangeFilter::make('Fine Lavori (Data)', 'completion_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('completion_date', '>=', $v['minDate'])->whereDate('completion_date', '<=', $v['maxDate'])),

            SelectFilter::make('Urgente', 'urgente_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('urgente', $value)),

            SelectFilter::make('Ordinaria', 'ordinaria_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('ordinaria', $value)),

            SelectFilter::make('Fine Lavori (Check)', 'fine_lavori_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('fine_lavori', $value)),

            DateRangeFilter::make('Data FL', 'data_fl_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('data_fl', '>=', $v['minDate'])->whereDate('data_fl', '<=', $v['maxDate'])),

            SelectFilter::make('RA', 'ra_filter')
                ->options($siNoOptions)
                ->filter(fn(Builder $builder, $value) => $builder->where('ra', $value)),

            DateRangeFilter::make('Data RA', 'data_ra_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('data_ra', '>=', $v['minDate'])->whereDate('data_ra', '<=', $v['maxDate'])),

            DateRangeFilter::make('Evaso dal DL', 'evaso_dl_filter')
                ->config(['locale' => 'it'])
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('evaso_dal_dl', '>=', $v['minDate'])->whereDate('evaso_dal_dl', '<=', $v['maxDate'])),

            TextFilter::make('Operatori Assegnati', 'assigned_operators')
                ->config(['placeholder' => 'Operatore'])
                ->filter(fn(Builder $builder, string $value) => $builder->whereHas('users', fn($q) => $q->where('name', 'like', "%$value%"))),

            DateRangeFilter::make('Mese Saldo', 'mese_saldo_filter')
                ->filter(fn(Builder $builder, array $v) => $builder->whereDate('mese_saldo', '>=', $v['minDate'])->whereDate('mese_saldo', '<=', $v['maxDate'])),

            TextFilter::make('Delta', 'delta_filter')
                ->filter(fn(Builder $builder, $value) => $builder->where('delta', $value)),

            TextFilter::make('Al DL', 'al_dl_filter')
                ->filter(fn(Builder $builder, $value) => $builder->where('al_dl', $value)),

            TextFilter::make('A NE', 'a_ne_filter')
                ->filter(fn(Builder $builder, $value) => $builder->where('a_ne', $value)),

            TextFilter::make('VDC1', 'vdc1_filter')->filter(fn(Builder $builder, $v) => $builder->where('vdc1', $v)),
            TextFilter::make('VDC2', 'vdc2_filter')->filter(fn(Builder $builder, $v) => $builder->where('vdc2', $v)),
            TextFilter::make('VDC3', 'vdc3_filter')->filter(fn(Builder $builder, $v) => $builder->where('vdc3', $v)),
            TextFilter::make('VDC4', 'vdc4_filter')->filter(fn(Builder $builder, $v) => $builder->where('vdc4', $v)),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Data Creazione', 'created_at')
                ->format(fn($value) => Carbon::parse($value)->setTimezone('Europe/Rome')->format('d/m/Y'))
                ->sortable()
                ->secondaryHeaderFilter('created_at'),

            Column::make('Progetto', 'progetto')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('progetto_filter'),

            Column::make('Regione', 'regione.nome')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('regione_id'),

            Column::make('Comune', 'comune.name')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('comune_id'),

            Column::make('Centrale', 'central.central')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('centrale_id'),

            Column::make('Status', 'status')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('status_filter')
                ->format(function ($value) {
                    if ($value == "In Lavorazione") {
                        return "<span class='badge rounded-pill text-bg-primary'>$value</span>";
                    } else if ($value == "Da Lavorare") {
                        return "<span class='badge rounded-pill text-bg-info'>$value</span>";
                    } else if ($value == "Consegnato") {
                        return "<span class='badge rounded-pill text-bg-warning'>$value</span>";
                    } else if ($value == "Fine Lavori") {
                        return "<span class='badge rounded-pill pill-fine-lavori'>$value</span>";
                    }
                    return $value;
                })->html(),

            Column::make('Descrizione', 'descrizione')
                ->sortable()
                ->secondaryHeaderFilter('descrizione_filter'),

            Column::make('Network', 'network')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('network_filter'),

            Column::make('Via', 'via')
                ->sortable()->searchable()
                ->secondaryHeaderFilter('via_filter'),

            Column::make('Consegna', 'consegna')
                ->format(fn($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '')
                ->sortable()
                ->secondaryHeaderFilter('consegna_filter'),

            Column::make('AP Chiusini', 'ap_chiusini')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('ap_chiusini_filter'),

            Column::make('Num. Chiusini', 'num_chiusini')
                ->sortable()
                ->secondaryHeaderFilter('num_chiusini_filter'),

            Column::make('Scavo 100m', 'scavo_fino_100m')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('scavo_filter'),

            Column::make('Quote Agg.', 'quote_aggiuntive')
                ->sortable()
                ->secondaryHeaderFilter('quote_filter'),

            Column::make('Presa in Carico', 'acception_date')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y H:i') : '-')
                ->sortable()
                ->secondaryHeaderFilter('acception_filter'),

            Column::make('Consegna Op.', 'delivery_date')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y H:i') : '-')
                ->sortable()
                ->secondaryHeaderFilter('delivery_filter'),

            Column::make('Fine Lavori (Data)', 'completion_date')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y H:i') : '-')
                ->sortable()
                ->secondaryHeaderFilter('completion_filter'),

            Column::make('Urgente', 'urgente')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('urgente_filter'),

            Column::make('Ordinaria', 'ordinaria')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('ordinaria_filter'),

            Column::make('Fine Lavori (Check)', 'fine_lavori')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('fine_lavori_filter'),

            Column::make('Data FL', 'data_fl')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '')
                ->secondaryHeaderFilter('data_fl_filter'),

            Column::make('RA', 'ra')
                ->format(fn($v) => $v ? 'SI' : 'NO')
                ->secondaryHeaderFilter('ra_filter'),

            Column::make('Data RA', 'data_ra')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '')
                ->secondaryHeaderFilter('data_ra_filter'),

            Column::make('Evaso dal DL', 'evaso_dal_dl')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '')
                ->secondaryHeaderFilter('evaso_dl_filter'),

            Column::make('Operatori Assegnati', "assigned_operators")
                ->label(function ($row, Column $column) {
                    return $row->users->pluck('name')->join(', ');
                })
                ->secondaryHeaderFilter('assigned_operators'),

            Column::make('Mese Saldo', 'mese_saldo')
                ->format(fn($v) => $v ? Carbon::parse($v)->format('m/Y') : '')
                ->secondaryHeaderFilter('mese_saldo_filter'),

            Column::make('Delta', 'delta')
                ->format(fn($v) => number_format((float) $v, 2, ',', '.'))
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('delta_filter'),

            Column::make('Al DL', 'al_dl')
                ->format(fn($v) => number_format((float) $v, 2, ',', '.'))
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('al_dl_filter'),

            Column::make('A NE', 'a_ne')
                ->format(fn($v) => number_format((float) $v, 2, ',', '.'))
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('a_ne_filter'),

            Column::make('VDC1', 'vdc1')
                ->format(fn($v) => $v ?? 0)
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('vdc1_filter'),

            Column::make('VDC2', 'vdc2')
                ->format(fn($v) => $v ?? 0)
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('vdc2_filter'),

            Column::make('VDC3', 'vdc3')
                ->format(fn($v) => $v ?? 0)
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('vdc3_filter'),

            Column::make('VDC4', 'vdc4')
                ->format(fn($v) => $v ?? 0)
                ->hideIf(!Auth::user()->hasRole('admin'))
                ->secondaryHeaderFilter('vdc4_filter'),

            Column::make('Azioni')
                ->label(fn($row) => view('permessi_ente.permessi-ente-table-actions', ['row' => $row]))
                ->html(),
        ];
    }
}
