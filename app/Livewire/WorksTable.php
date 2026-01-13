<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\BooleanFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateTimeFilter;
use Rappasoft\LaravelLivewireTables\Views\Columns\ComponentColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class WorksTable extends DataTableComponent
{
    protected $model = Work::class;

    // public function builder(): Builder
    // {
    //     return Work::query()->orderByDesc('created_at')

    // }
    protected $listeners = [
        'workUpdated' => '$refresh',
    ];


    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(true);
        $this->setAdditionalSelects(['works.id as id']);

        $this->setDefaultSort('created_at', 'desc');

        $this->setPerPageAccepted([10, 25, 50, 100, 200, 300]);

        $this->setTableAttributes([
            'class' => 'table-hover'
        ]);

        $this->setTdAttributes(function (Column $column) {
            if ($column->isField("created_at") || $column->isField("completion_date")) {
                return [
                    'class' => "text-nowrap "
                ];
            }

            return [];
        });

    }

    public function filters(): array
    {
        $companies = ['' => 'Tutte'];
        $companies = array_merge($companies, Company::pluck('name', 'name')->toArray());

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

            DateRangeFilter::make('Data FL', 'completion_date')->config([
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
                        ->whereDate('works.completion_date', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('works.completion_date', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),



            SelectFilter::make('Impresa', 'company')->options($companies)->filter(function (Builder $builder, string $value) {
                $builder->whereHas('company', function ($query) use ($value) {
                    $query->where('name', 'like', "%$value%");
                })->get();
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
                    'Fine Lavori' => 'Fine Lavori',

                ])->filter(function (Builder $builder, string $value) {
                    $builder->where('status', $value);
                }),

            SelectFilter::make('Ambito NTW', 'ntw_scope')
                ->options([
                    '' => 'Tutti',
                    'FTTH' => 'FTTH',
                    'FTTH PTE' => 'FTTH PTE',
                    'FTTH PNRR' => 'FTTH PNRR',
                    '5G' => '5G',
                    'REACTIVE' => 'REACTIVE',
                    'INCREMENTALE' => 'INCREMENTALE',
                    'DESATURAZIONE' => 'DESATURAZIONE',
                    'NGAN' => 'NGAN',
                    'GIUNZIONE' => 'GIUNZIONE',
                    'SUB-LOOP' => 'SUB-LOOP',


                ])->filter(function (Builder $builder, string $value) {
                    $builder->where('ntw_scope', $value);
                }),


            SelectFilter::make('Fase', 'phase')
                ->options([
                    '' => 'Tutti',
                    'Fase 1' => 'Fase 1',
                    'Fase 2' => 'Fase 2',
                    'Aggiornamento' => 'Aggiornamento',
                    'Modifica' => 'Modifica',
                ])->filter(function (Builder $builder, string $value) {
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
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('users', function ($query) use ($value) {
                        $query->where('name', 'like', "%$value%");
                    })->get();

                }),

            SelectFilter::make('Daphne', 'daphne')
                ->options([
                    '' => 'Tutti',
                    '1' => 'Si',
                    '0' => 'No',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === '1') {
                        $builder->where('daphne', 1);
                    } elseif ($value === '0') {
                        $builder->where('daphne', 0);
                    }
                }),
        ];
    }


    public function columns(): array
    {
        return [
            Column::make('Data creazione', 'created_at')
                ->format(function ($value, $row, Column $column) {
                    // $value è un Carbon o stringa a seconda del cast del model
                    return Carbon::parse($value)
                        ->setTimezone('Europe/Rome')
                        ->format('d/m/Y');
                })
                ->sortable()->secondaryHeaderFilter(filterKey: 'created_at'),
            Column::make("Impresa", "company.name")
                ->sortable()->searchable()->secondaryHeaderFilter(filterKey: 'company'),
            Column::make("Centrale", "central.central")
                ->sortable()->searchable(),
            Column::make("Regione", "central.region")
                ->sortable()->searchable(),
            Column::make("Status", "status")
                ->sortable()->searchable()->secondaryHeaderFilter('status')->format(function ($value) {
                    if ($value == "Sospeso") {
                        return "<span class='badge rounded-pill text-bg-warning'>$value</span>";
                    } else if ($value == "In Lavorazione") {
                        return "<span class='badge rounded-pill text-bg-primary'>$value</span>";
                    } else if ($value == "Da Lavorare") {
                        return "<span class='badge rounded-pill text-bg-info'>$value</span>";
                    } else if ($value == "KO") {
                        return "<span class='badge rounded-pill text-bg-danger'>$value</span>";
                    } else if ($value == "Consegnato") {
                        return "<span class='badge rounded-pill text-bg-success'>$value</span>";
                    } else if ($value == "Attesa Fine Lavori") {
                        return "<span class='badge rounded-pill bg-warning-subtle text-white'>$value</span>";
                    } else if ($value == "Fine Lavori") {
                        return "<span class='badge rounded-pill pill-fine-lavori'>$value</span>";
                    }

                    return $value;
                })->html(),
            Column::make("Descrizione", "description")
                ->sortable(),
            Column::make("Ambito NTW", "ntw_scope")
                ->sortable()->searchable()->secondaryHeaderFilter(filterKey: 'ntw_scope'),
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
            Column::make('Daphne', 'daphne')
                ->format(fn($value) => $value ? 'SI' : 'NO')
                ->secondaryHeaderFilter('daphne'),
            Column::make('Operatori Assegnati', "assigned_operators")
                ->label(function ($row, Column $column) {
                    // DIRECT ACCESS - No access to DB
                    return $row->users->pluck('name')->join(', ');
                })->secondaryHeaderFilter(filterKey: 'assigned_operators'),
            Column::make("Data PiC", "acception_date")->format(
                function ($value, $row, Column $column) {
                    if ($value) {
                        return Carbon::parse($row->acception_date)
                            ->setTimezone('Europe/Rome')
                            ->format('d/m/Y H:i');
                    }
                }
            )
                ->sortable(),
            Column::make("Data Consegna", "delivery_date")->format(
                function ($value, $row, Column $column) {
                    if ($value) {
                        return Carbon::parse($row->delivery_date)
                            ->setTimezone('Europe/Rome')
                            ->format('d/m/Y H:i');
                    }
                }
            )
                ->sortable(),
            Column::make("Data FL", "completion_date")->format(
                function ($value, $row, Column $column) {
                    if ($value) {
                        return Carbon::parse($row->completion_date)
                            ->setTimezone('Europe/Rome')
                            ->format('d/m/Y');
                    }
                }
            )
                ->sortable()->secondaryHeaderFilter(filterKey: 'completion_date'),
            Column::make("Assistente Impresa", "company_assistant")->deselected()
            ,
            Column::make("Note", "notes")->deselected(),
            Column::make("Storico sospensioni", "suspension_history")->deselected()
            ,
            Column::make('Actions')->label(function ($row, Column $column) {
                return view('works.works-table-actions')->with('row', $row);
            })->html(),
        ];
    }
}
