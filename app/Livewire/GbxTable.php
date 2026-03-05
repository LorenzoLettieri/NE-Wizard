<?php
namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Gbx;
use App\Models\Central;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Filters\TextFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateRangeFilter;
use App\Models\Company;

class GbxTable extends DataTableComponent
{
    // protected $model = Gbx::class;
    public function builder(): Builder
    {
        return Gbx::query()
            ->with(['company', 'central'])
            ->withCount('media')
            ->when(!auth()->user()->hasAnyRole('admin|GBX Supervisor'), function ($query) {
                $query->whereHas('company', fn($q) => $q->where('name', auth()->user()->company->name));
            });
    }
    protected $listeners = [
        'gbxUpdated' => '$refresh',
    ];

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setSearchLive();
        $this->setDefaultSort('created_at', 'desc');
        $this->setPerPageAccepted([10, 25, 50, 100]);
        $this->setTableAttributes(['class' => 'table-hover']);
        $this->setSecondaryHeaderStatus(true);
    }

    public function filters(): array
    {
        $companies = ['' => 'Tutte'];
        $companies = array_merge($companies, Company::pluck('name', 'name')->toArray());

        $centrals = ['' => 'Tutte'];
        $centrals = array_merge($centrals, Central::pluck('central', 'central')->toArray());

        return [
            DateRangeFilter::make('Data Creazione', 'date')->config([
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
                        ->whereDate('gbxes.date', '>=', $dateRange['minDate']) // minDate is the start date selected
                        ->whereDate('gbxes.date', '<=', $dateRange['maxDate']); // maxDate is the end date selected
                }),

            SelectFilter::make('Impresa', 'company')
                ->options($companies)
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('company', fn($q) => $q->where('name', $value));
                }),

            TextFilter::make('Network', 'network')
                ->config(['placeholder' => 'Network'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('network', 'like', "%$value%");
                }),

            TextFilter::make('SDF', 'sdf')
                ->config(['placeholder' => 'SDF'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('SDF', 'like', "%$value%");
                }),

            SelectFilter::make('Centrale', 'central')
                ->options($centrals)
                ->filter(function (Builder $builder, string $value) {
                    $builder->whereHas('central', fn($q) => $q->where('central', $value));
                }),

            TextFilter::make('Comune', 'comune')
                ->config(['placeholder' => 'Comune'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('comune', 'like', "%$value%");
                }),

            TextFilter::make('Cliente', 'client')
                ->config(['placeholder' => 'Cliente'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('client', 'like', "%$value%");
                }),

            SelectFilter::make('Adeguato', 'is_adeguate')
                ->options(['' => 'Tutti', '1' => 'SI', '0' => 'NO'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('is_adeguate', $value);
                }),

            DateRangeFilter::make('Data Creazione', 'created_at')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('gbxes.created_at', '>=', $dateRange['minDate'])
                        ->whereDate('gbxes.created_at', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Appuntamento', 'appointment_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('appointment_date', '>=', $dateRange['minDate'])
                        ->whereDate('appointment_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Ispezione', 'inspection_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('inspection_date', '>=', $dateRange['minDate'])
                        ->whereDate('inspection_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Verbale', 'verbal_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('verbal_date', '>=', $dateRange['minDate'])
                        ->whereDate('verbal_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Obbligo', 'obligation_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('obligation_date', '>=', $dateRange['minDate'])
                        ->whereDate('obligation_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Rilascio', 'release_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('release_date', '>=', $dateRange['minDate'])
                        ->whereDate('release_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Rich. Permessi', 'permission_request_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('permission_request_date', '>=', $dateRange['minDate'])
                        ->whereDate('permission_request_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Ott. Permessi', 'permission_obtain_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('permission_obtain_date', '>=', $dateRange['minDate'])
                        ->whereDate('permission_obtain_date', '<=', $dateRange['maxDate']);
                }),

            SelectFilter::make('Permessi', 'permissions')
                ->options(['' => 'Tutti', '1' => 'SI', '0' => 'NO'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('permissions', $value);
                }),

            SelectFilter::make('Avanz. CO', 'CO_advancement')
                ->options(['' => 'Tutti', '1' => 'SI', '0' => 'NO'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('CO_advancement', $value);
                }),

            TextFilter::make('Coordinate', 'coordinates')
                ->config(['placeholder' => 'Coordinate'])
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('coordinates', 'like', "%$value%");
                }),

            DateRangeFilter::make('Data Progetto', 'project_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('project_date', '>=', $dateRange['minDate'])
                        ->whereDate('project_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Speedark', 'speedark_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('speedark_date', '>=', $dateRange['minDate'])
                        ->whereDate('speedark_date', '<=', $dateRange['maxDate']);
                }),

            DateRangeFilter::make('Data Agg. Cart', 'cart_update_date')
                ->config(['locale' => 'it'])
                ->filter(function (Builder $builder, array $dateRange) {
                    $builder->whereDate('cart_update_date', '>=', $dateRange['minDate'])
                        ->whereDate('cart_update_date', '<=', $dateRange['maxDate']);
                }),
        ];
    }

    public function columns(): array
    {
        $columns = [
            Column::make('Data creazione', 'date')
                ->format(function ($value, $row, Column $column) {
                    if (!$value) {
                        return '-';
                    }
                    // $value è un Carbon o stringa a seconda del cast del model
                    return Carbon::parse($value)
                        ->setTimezone('Europe/Rome')
                        ->format('d/m/Y');
                })
                ->sortable()->secondaryHeaderFilter(filterKey: 'date'),
            Column::make("ID", "id")->sortable(),
            Column::make("Impresa", "company.name")->sortable()->searchable()->secondaryHeaderFilter('company'),
            Column::make("Network", "network")->sortable()->searchable()->secondaryHeaderFilter('network'),
            Column::make("SDF", "SDF")->sortable()->searchable()->secondaryHeaderFilter('sdf'),
            Column::make("Centrale", "central.central")->sortable()->searchable()->secondaryHeaderFilter('central'),
            Column::make("Comune", "comune")->sortable()->searchable()->secondaryHeaderFilter('comune'),
            Column::make("Cliente", "client")->sortable()->searchable()->secondaryHeaderFilter('client'),
            Column::make("Data Appunt.", "appointment_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') :
                '-')->sortable()->secondaryHeaderFilter('appointment_date'),
            Column::make("Data Soprall.", "inspection_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') :
                '-')->sortable()->secondaryHeaderFilter('inspection_date'),
            Column::make("Data Verbale", "verbal_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('verbal_date'),
            Column::make("Infr. Adeguata", "is_adeguate")->format(fn($v) => $v ? 'SI' : 'NO')->sortable()->secondaryHeaderFilter('is_adeguate'),
            Column::make("Data Vincolo", "obligation_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('obligation_date'),
            Column::make("Data Rilascio", "release_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('release_date'),
            Column::make("Rich. Permessi", "permission_request_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('permission_request_date'),
            Column::make("Ott. Permessi", "permission_obtain_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('permission_obtain_date'),
            Column::make("Permessi", "permissions")->format(fn($v) => $v ? 'SI' : 'NO')->sortable()->secondaryHeaderFilter('permissions'),
            Column::make("Avanz. CO", "CO_advancement")->format(fn($v) => $v ? 'SI' : 'NO')->sortable()->secondaryHeaderFilter('CO_advancement'),
            Column::make("Coordinate", "coordinates")->sortable()->searchable()->secondaryHeaderFilter('coordinates'),
            Column::make("Data Progetto", "project_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('project_date'),
            Column::make("Data Speedark", "speedark_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('speedark_date'),
            Column::make("Data Agg. Cart", "cart_update_date")->format(fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : '-')->sortable()->secondaryHeaderFilter('cart_update_date'),
            Column::make("Allegati")
                ->label(fn($row) => ($row->media_count ?? 0) > 0
                    ? '<span class="badge rounded-pill text-bg-success" title="Allegati presenti"><i class="bi bi-check-lg"></i></span>'
                    : '<span class="badge rounded-pill text-bg-danger" title="Nessun allegato"><i class="bi bi-x-lg"></i></span>')
                ->html(),
        ];

        if (auth()->user()->hasRole('admin')) {
            $columns[] = Column::make("Valore", "value")->format(fn($v) => $v ? number_format($v, 2, ',', '.') . ' €' : '-')->sortable();
            $columns[] = Column::make("Pagato Impresa", "company_paid")->format(fn($v) => $v ? number_format($v, 2, ',', '.') . ' €' : '-')->sortable();
            $columns[] = Column::make("Pagato Bezzi", "bezzi_paid")->format(fn($v) => $v ? number_format($v, 2, ',', '.') . ' €' : '-')->sortable();
            $columns[] = Column::make("Pagato Progetto", "project_paid")->format(fn($v) => $v ? number_format($v, 2, ',', '.') . ' €' : '-')->sortable();
            $columns[] = Column::make("Pagato DL", "dl_paid")->format(fn($v) => $v ? number_format($v, 2, ',', '.') . ' €' : '-')->sortable();
        }

        $columns[] = Column::make("Azioni")->label(fn($row) => view('gbxes.gbxes-table-actions')->with('row', $row))->html();

        return $columns;
    }
}
