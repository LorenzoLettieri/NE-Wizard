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
    public function builder(): Builder{
        // return Work::query()->with('users')->where('users.id', $current_user->id);
        if(Auth::user()->hasAnyRole('admin','supervisor')){
            return PermessoEnte::query()->with('users');
        } 
        
        return PermessoEnte::query()
        ->with('users')
        ->whereHas('users', function (Builder $query) {
            $query->where('users.id', Auth::id());
        });
            
    }

    protected $listeners = [
        'permessoUpdated' => '$refresh',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(true);
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
            ->config([
                'allowInput' => true,
                'altFormat' => 'F j, Y',
                'ariaDateFormat' => 'F j, Y',
                'dateFormat' => 'Y-m-d',
                'placeholder' => 'Inserisci Data',
                'locale' => 'it',
            ])
            ->filter(function (Builder $builder, array $dateRange) {
                $builder->whereDate('permessi_ente.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('permessi_ente.created_at', '<=', $dateRange['maxDate']);
            }),

        SelectFilter::make('Regione', 'regione_id')
            ->options($regioni)
            ->filter(fn (Builder $builder, $value) => $builder->where('regione_id', $value)),

        SelectFilter::make('Comune', 'comune_id')
            ->options($comuni)
            ->filter(fn (Builder $builder, $value) => $builder->where('comune_id', $value)),

        SelectFilter::make('Centrale', 'centrale_id')
            ->options($centrali)
            ->filter(fn (Builder $builder, $value) => $builder->where('centrale_id', $value)),

        // ✅ Sostituiti i BooleanFilter con SelectFilter SI/NO
        SelectFilter::make('Urgente', 'urgente')
            ->options($siNoOptions)
            ->filter(fn (Builder $builder, $value) => $builder->where('urgente', $value)),

        SelectFilter::make('Ordinaria', 'ordinaria')
            ->options($siNoOptions)
            ->filter(fn (Builder $builder, $value) => $builder->where('ordinaria', $value)),

        SelectFilter::make('Fine Lavori', 'fine_lavori')
            ->options($siNoOptions)
            ->filter(fn (Builder $builder, $value) => $builder->where('fine_lavori', $value)),
    ];
}

    public function columns(): array
    {
        return [
            Column::make('Data Creazione', 'created_at')
                ->format(fn ($value) => Carbon::parse($value)->setTimezone('Europe/Rome')->format('d/m/Y'))
                ->sortable(),

            Column::make('Progetto', 'progetto')
                ->sortable()->searchable(),

            Column::make('Regione', 'regione.nome')
                ->sortable()->searchable(),

            Column::make('Comune', 'comune.name')
                ->sortable()->searchable(),

            Column::make('Centrale', 'central.central')
                ->sortable()->searchable(),

            Column::make('Descrizione', 'descrizione')
                ->sortable(),

            Column::make('Network', 'network')
                ->sortable()->searchable(),

            Column::make('Consegna', 'consegna')
                ->format(fn ($value) => $value ? Carbon::parse($value)->format('d/m/Y') : '')
                ->sortable(),

            Column::make('Fine Lavori', 'fine_lavori')
                ->format(fn ($v) => $v ? 'SI' : 'NO'),

            Column::make('Urgente', 'urgente')
                ->format(fn ($v) => $v ? 'SI' : 'NO'),

            Column::make('Ordinaria', 'ordinaria')
                ->format(fn ($v) => $v ? 'SI' : 'NO'),

            Column::make('RA', 'ra')
                ->format(fn ($v) => $v ? 'SI' : 'NO'),

            Column::make('Data RA', 'data_ra')
                ->format(fn ($v) => $v ? Carbon::parse($v)->format('d/m/Y') : ''),

            Column::make('Mese Saldo', 'mese_saldo')
                ->format(fn ($v) => $v ? Carbon::parse($v)->format('m/Y') : ''),

            Column::make('Delta', 'delta')
                ->format(fn ($v) => number_format($v, 2, ',', '.')),

            Column::make('Azioni')
                ->label(fn ($row) => view('permessi_ente.permessi-ente-table-actions', ['row' => $row]))
                ->html(),
        ];
    }
}
