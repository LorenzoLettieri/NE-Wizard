<?php

namespace App\Livewire;

use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateTimeFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class WorksTable extends DataTableComponent
{
    protected $model = Work::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(true);
        $this->setAdditionalSelects(['works.id as id']);
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Created at', 'created_at')->config([
            'allowInput' => true,   // Allow manual input of dates
            'altFormat' => 'F j, Y', // Date format that will be displayed once selected
            'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
            'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
            'placeholder' => 'Enter Date Range', // A placeholder value
            'locale' => 'en',
        ])
        ->setFilterPillValues([0 => 'minDate', 1 => 'maxDate']) // The values that will be displayed for the Min/Max Date Values
        ->filter(function (Builder $builder, array $dateRange) { // Expects an array.
            $builder
                ->whereDate('works.created_at', '>=', $dateRange['minDate']) // minDate is the start date selected
                ->whereDate('works.created_at', '<=', $dateRange['maxDate']); // maxDate is the end date selected
        }),

        SelectFilter::make('Status', 'status')
            ->options([
                '' => 'Tutti',
                'Da Lavorare' => 'Da Lavorare',
                'In Lavorazione' => 'In Lavorazione',
                'Sospeso' => 'Sospeso',
                'Attesa Fine Lavori' => 'Attesa Fine Lavori',
                'KO' => 'KO',
            ])->filter(function(Builder $builder, string $value){
                $builder->where('status', $value);
            }),

        SelectFilter::make('Fase', 'phase')
            ->options([
                '' => 'Tutti',
                'Fase 1' => 'Fase 1',
                'Fase 2' => 'Fase 2',
                'Aggiornamento' => 'Aggiornamento',
                'Modifica' => 'Modifica',
            ])->filter(function(Builder $builder, string $value){
                $builder->where('phase', $value);
            }),

        TextFilter::make('WO Number', 'wo_number')
            ->config([
                'placeholder' => 'WO Number',
            ])
            ->contains('works.wo_number'),

        TextFilter::make('Network', 'network')
            ->config([
                'placeholder' => 'NTW Number',
            ])
            ->contains('works.network'),

        TextFilter::make('Unica Number', 'unica_number')
            ->config([
                'placeholder' => 'Unica Number',
            ])
            ->contains('works.unica_number'),

        TextFilter::make('AO/CNO', 'ao_cno')
            ->config([
                'placeholder' => 'AO/CNO',
            ])
            ->contains('works.ao_cno'),

        TextFilter::make('Assigned Operators', 'assigned_operators')
            ->config([
                'placeholder' => 'Operatore',
            ])
            ->filter(function(Builder $builder, string $value){
                $builder->whereHas('users', function ($query) use ($value) {
                    $query->where('name', 'like', "%$value%");
                })->get();

            }),
                

        ];
    }


    public function columns(): array
    {
        return [
            Column::make("Created at", "created_at")
            ->sortable()->secondaryHeaderFilter(filterKey: 'created_at'),
            Column::make("Company", "company.name")
                ->sortable()->searchable(),
            Column::make("Central", "central.central")
                ->sortable()->searchable(),
            Column::make("Region", "central.region")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->sortable()->searchable()->secondaryHeaderFilter('status'),
            Column::make("Description", "description")
                ->sortable(),
            Column::make("Ntw scope", "ntw_scope")
                ->sortable()->searchable(),
            Column::make("Type", "type")
                ->sortable()->searchable(),
            Column::make("Phase", "phase")
                ->sortable()->searchable()->secondaryHeaderFilter('phase'),
            Column::make("Nroe", "nroe")
                ->sortable(),
                Column::make("Network", "network")
                ->sortable()->searchable()->secondaryHeaderFilter('network'),
            Column::make("Wo number", "wo_number")
                ->sortable()->searchable()->secondaryHeaderFilter('wo_number'),
            Column::make("Unica number", "unica_number")
                ->sortable()->searchable()->secondaryHeaderFilter('unica_number'),
            Column::make("AO/CNO", "ao_cno")->setCustomSlug('AO CNO')
                ->sortable()->searchable()->secondaryHeaderFilter('ao_cno'),
            Column::make('Assigned Operators', "assigned_operators")
                ->label(function ($row, Column $column){
                    $work = Work::find($row->id);
                    return $work->users->pluck('name')->join(', ');
                })->secondaryHeaderFilter(filterKey: 'assigned_operators'),
            Column::make("Acception date", "acception_date")
                ->sortable(),
            Column::make("Completion date", "completion_date")
                ->sortable(),
            Column::make("Delivery date", "delivery_date")
                ->sortable(),
            Column::make('Actions')->label(function ($row, Column $column){
                return view('works.works-table-actions')->with('row', Work::find($row->id));
            })->html(),
            
        ];
    }

    

}
