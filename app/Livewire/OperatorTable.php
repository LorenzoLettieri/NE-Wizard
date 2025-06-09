<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Work;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class OperatorTable extends DataTableComponent
{
    
    public function builder(): Builder{
        // return Work::query()->with('users')->where('users.id', $current_user->id);
        return Work::query()
        ->with('users')
        ->whereHas('users', function (Builder $query) {
            $query->where('users.id', Auth::id());
        });
            
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(true);
        $this->setAdditionalSelects(['works.id as id']);

        //style
        $this->setTableAttributes([
            'class' => 'table-hover'
        ]);
    }

    public function filters(): array
    {
        return [
            DateRangeFilter::make('Data Creazione', 'created_at')->config([
            'allowInput' => true,   // Allow manual input of dates
            'altFormat' => 'F j, Y', // Date format that will be displayed once selected
            'ariaDateFormat' => 'F j, Y', // An aria-friendly date format
            'dateFormat' => 'Y-m-d', // Date format that will be received by the filter
            'placeholder' => 'Inserisci Data', // A placeholder value
            'locale' => 'it',
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
                'Consegnato' => 'Consegnato',

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

        TextFilter::make('Numero WO', 'wo_number')
            ->config([
                'placeholder' => 'WO Number',
            ])
            ->contains('works.wo_number'),

        TextFilter::make('Network', 'network')
            ->config([
                'placeholder' => 'NTW Number',
            ])
            ->contains('works.network'),

        TextFilter::make('Numero UNICA', 'unica_number')
            ->config([
                'placeholder' => 'Unica Number',
            ])
            ->contains('works.unica_number'),

        TextFilter::make('AO/CNO', 'ao_cno')
            ->config([
                'placeholder' => 'AO/CNO',
            ])
            ->contains('works.ao_cno'),

        TextFilter::make('Operatori Assegnati', 'assigned_operators')
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
            DateColumn::make("Data", "created_at")
            ->sortable()->secondaryHeaderFilter(filterKey: 'created_at'),
            Column::make("Impresa", "company.name")
                ->sortable()->searchable(),
            Column::make("Centrale", "central.central")
                ->sortable()->searchable(),
            Column::make("Regione", "central.region")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->sortable()->searchable()->secondaryHeaderFilter('status')->format(function($value){
                    if($value == "Sospeso"){
                        return "<span class='badge rounded-pill text-bg-warning'>$value</span>";
                    } 
                    else if($value == "In Lavorazione"){
                        return "<span class='badge rounded-pill text-bg-primary'>$value</span>";
                    }
                    else if($value == "Da Lavorare"){
                        return "<span class='badge rounded-pill text-bg-info'>$value</span>";
                    }
                    else if($value == "KO"){
                        return "<span class='badge rounded-pill text-bg-danger'>$value</span>";
                    }
                    else if($value == "Consegnato"){
                        return "<span class='badge rounded-pill text-bg-success'>$value</span>";
                    }

                    return $value;
                })->html(),
            Column::make("Descrizione", "description")
                ->sortable(),
            Column::make("Ambito NTW", "ntw_scope")
                ->sortable()->searchable(),
            Column::make("Tipo", "type")
                ->sortable()->searchable(),
            Column::make("Fase", "phase")
                ->sortable()->searchable()->secondaryHeaderFilter('phase'),
            Column::make("N.Roe", "nroe")
                ->sortable(),
                Column::make("Network", "network")
                ->sortable()->searchable()->secondaryHeaderFilter('network'),
            Column::make("WO", "wo_number")
                ->sortable()->searchable()->secondaryHeaderFilter('wo_number'),
            Column::make("UNICA", "unica_number")
                ->sortable()->searchable()->secondaryHeaderFilter('unica_number'),
            Column::make("AO/CNO", "ao_cno")->setCustomSlug('AO CNO')
                ->sortable()->searchable()->secondaryHeaderFilter('ao_cno'),
            Column::make('Actions')->label(function ($row, Column $column){
                return view('operator.operator-table-actions')->with('row', Work::find($row->id));
            })->html(),
            
        ];
    }

    public function takeWork($id){
        $acceptedWork = Work::find($id);
        $acceptedWork->status = 'In Lavorazione';
        $acceptedWork->acception_date = Carbon::now();
        $acceptedWork->save();
    }

    public function deliveryWork($id){
        $acceptedWork = Work::find($id);
        $acceptedWork->status = 'Consegnato';
        $acceptedWork->delivery_date = Carbon::now();
        $acceptedWork->save();

    
    }
}
