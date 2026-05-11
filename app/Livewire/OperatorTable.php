<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\NetworkScope;
use App\Models\Work;
use App\Models\WorkPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Columns\DateColumn;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;

class OperatorTable extends DataTableComponent
{
    use AuthorizesRequests;

    public ?int $targetOperatorId = null;

    public bool $readOnlyMode = false;

    public function mount(?int $targetOperatorId = null, bool $readOnlyMode = false): void
    {
        $this->targetOperatorId = $targetOperatorId;
        $this->readOnlyMode = $readOnlyMode;
    }
    
    public function builder(): Builder{
        $operatorId = $this->scopedOperatorId();

        return Work::query()
        ->with(['users', 'company', 'central', 'workPhase', 'networkScope'])
        ->whereHas('users', function (Builder $query) use ($operatorId) {
            $query->where('users.id', $operatorId);
        });
            
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setEagerLoadAllRelationsStatus(true);
        $this->setAdditionalSelects([
            'works.id as id',
            'works.phase as phase',
            'works.work_phase_id as work_phase_id',
        ]);

        $this->setDefaultSort('created_at', 'desc');

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

        SelectFilter::make('Fase', 'work_phase_id')
            ->options(['' => 'Tutti'] + WorkPhase::options())
            ->filter(function(Builder $builder, string $value){
                $builder->where('work_phase_id', $value);
            }),

        SelectFilter::make('Ambito NTW', 'network_scope_id')
            ->options(['' => 'Tutti'] + NetworkScope::options())
            ->filter(function(Builder $builder, string $value){
                $builder->where('network_scope_id', $value);
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
            Column::make("Data", "created_at")->format(
                function($value,$row, Column $column) {
                    if($value){
                        return Carbon::parse($value)
                            ->setTimezone('Europe/Rome')
                            ->format('d/m/Y');
                    }
            })
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
                    else if($value == "Attesa Fine Lavori"){
                        return "<span class='badge rounded-pill bg-warning-subtle text-white'>$value</span>";
                    }
                    else if($value == "Fine Lavori"){
                        return "<span class='badge rounded-pill pill-fine-lavori'>$value</span>";
                    }

                    return $value;
                })->html(),
            Column::make("Descrizione", "description")
                ->sortable(),
            Column::make("Ambito NTW", "ntw_scope")
                ->sortable()->searchable(),
            Column::make("Tipo", "type")
                ->sortable()->searchable(),
            Column::make("Fase")
                ->label(fn ($row) => $row->workPhase?->name ?? $row->phase ?? '')
                ->searchable(function (Builder $builder, string $searchTerm) {
                    $builder->where(function (Builder $query) use ($searchTerm) {
                        $query->where('works.phase', 'like', "%{$searchTerm}%")
                            ->orWhereHas('workPhase', function (Builder $phaseQuery) use ($searchTerm) {
                                $phaseQuery->where('name', 'like', "%{$searchTerm}%");
                            });
                    });
                })
                ->secondaryHeaderFilter('work_phase_id'),
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
                ->format(fn($value) => $value ? 'SI' : 'NO'),
            Column::make("Data prevista consegna", "expected_delivery_date")->format(
                function ($value, $row, Column $column) {
                    if ($value) {
                        return Carbon::parse($row->expected_delivery_date)
                            ->setTimezone('Europe/Rome')
                            ->format('d/m/Y');
                    }
                }
            )
                ->sortable(),
            Column::make('Actions')->label(function ($row, Column $column){
                return view('operator.operator-table-actions', [
                    'row' => Work::find($row->id),
                    'readOnlyMode' => $this->readOnlyMode,
                ]);
            })->html(),
            
        ];
    }

    public function takeWork($id){
        abort_if($this->readOnlyMode, 403);

        DB::transaction(function () use ($id): void {
            $acceptedWork = Work::query()->lockForUpdate()->findOrFail($id);
            $this->authorize('operate', $acceptedWork);
            $acceptedWork->transitionToStatus(
                'In Lavorazione',
                Carbon::now(),
                forceAcceptanceDate: true,
            );
        });
    }

    public function deliveryWork($id){
        abort_if($this->readOnlyMode, 403);

        DB::transaction(function () use ($id): void {
            $acceptedWork = Work::query()->lockForUpdate()->findOrFail($id);
            $this->authorize('operate', $acceptedWork);
            $acceptedWork->transitionToStatus('Consegnato', Carbon::now());
        });
    }

    public function suspendWork($id){
        abort_if($this->readOnlyMode, 403);

        DB::transaction(function () use ($id): void {
            $acceptedWork = Work::query()->lockForUpdate()->findOrFail($id);
            $this->authorize('operate', $acceptedWork);
            $acceptedWork->transitionToStatus('Sospeso', Carbon::now());
        });
    }

    public function unsuspendWork($id){
        abort_if($this->readOnlyMode, 403);

        DB::transaction(function () use ($id): void {
            $acceptedWork = Work::query()->lockForUpdate()->findOrFail($id);
            $this->authorize('operate', $acceptedWork);
            $acceptedWork->transitionToStatus('In Lavorazione', Carbon::now());
        });
    }

    protected function scopedOperatorId(): int
    {
        if ($this->targetOperatorId !== null && Auth::user()?->hasRole('admin')) {
            return $this->targetOperatorId;
        }

        return (int) Auth::id();
    }
}
