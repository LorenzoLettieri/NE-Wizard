<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Central;
use App\Models\Comune;
use App\Models\Regione;
use App\Models\Company;
use App\Models\CompanyDecommissioningRate;
use App\Models\CompanyWorkPhaseRate;
use App\Models\NetworkScope;
use App\Models\ProductionTarget;
use App\Models\WorkPhase;
use Illuminate\Support\Facades\Cache;

class AdminBaseTables extends Component
{
    use WithPagination;

    private const PER_PAGE = 20;

    protected $paginationTheme = 'bootstrap';

    public $activeTab = 'Central';
    public string $search = '';
    
    // Modal states
    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    // Form data (we use a generic array to hold fields based on active tab)
    public $formData = [];
    public array $rateValues = [];
    public array $decommissioningRateValues = [];
    public array $productionTargetValues = [];

    // All possible fields across our models for resetting
    protected $defaultForm = [
        'central' => '',
        'region' => '',
        'comune_progressive' => '',
        'code' => '',
        'name' => '',
        'location' => '',
        'sovracomune' => '',
        'catasto_code' => '',
        'regione_id' => '',
        'nome' => '',
        'score_coefficient' => '',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->resetPage();
        $this->resetModal();

        if ($this->activeTab === 'CompanyWorkPhaseRate') {
            $this->loadRateValues();
        }

        if ($this->activeTab === 'CompanyDecommissioningRate') {
            $this->loadDecommissioningRateValues();
        }

        if ($this->activeTab === 'ProductionTarget') {
            $this->loadProductionTargetValues();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function resetForm()
    {
        $this->formData = $this->defaultForm;
    }

    public function resetModal()
    {
        $this->showModal = false;
        $this->isEditing = false;
        $this->editingId = null;
        $this->resetForm();
    }

    public function openCreateModal()
    {
        $this->resetModal();
        $this->showModal = true;
    }

    public function openEditModal($id)
    {
        $this->resetModal();
        $this->isEditing = true;
        $this->editingId = $id;

        $record = $this->getModelClass()::find($id);

        if ($record) {
            foreach ($record->getAttributes() as $key => $value) {
                $this->formData[$key] = $value;
            }
        }

        $this->showModal = true;
    }

    public function getModelClass()
    {
        return match($this->activeTab) {
            'Central' => Central::class,
            'Comune' => Comune::class,
            'Regione' => Regione::class,
            'Company' => Company::class,
            'WorkPhase' => WorkPhase::class,
            'NetworkScope' => NetworkScope::class,
            'CompanyWorkPhaseRate' => CompanyWorkPhaseRate::class,
            'CompanyDecommissioningRate' => CompanyDecommissioningRate::class,
            'ProductionTarget' => ProductionTarget::class,
            default => null,
        };
    }

    public function saveRecord()
    {
        if ($this->activeTab === 'Comune' && ($this->formData['regione_id'] ?? '') === '') {
            $this->formData['regione_id'] = null;
        }

        // Add basic validation depending on the tab
        $rules = [];
        if ($this->activeTab === 'Central') {
            $rules = [
                'formData.central' => 'required|string|max:255',
                'formData.region' => 'nullable|string|max:255',
            ];
        } elseif ($this->activeTab === 'Comune') {
            $rules = [
                'formData.comune_progressive' => 'required|string|max:255',
                'formData.name' => 'required|string|max:255',
                'formData.code' => 'required|string|max:255',
                'formData.location' => 'nullable|string|max:255',
                'formData.regione_id' => 'nullable|integer|exists:regioni,id',
                'formData.sovracomune' => 'nullable|string|max:255',
                'formData.catasto_code' => 'nullable|string|max:255',
            ];
        } elseif ($this->activeTab === 'Regione') {
            $rules = [
                'formData.nome' => 'required|string|max:255',
            ];
        } elseif ($this->activeTab === 'Company') {
            $rules = [
                'formData.name' => 'required|string|max:255',
            ];
        } elseif ($this->activeTab === 'WorkPhase') {
            $ignoreId = $this->isEditing && $this->editingId ? ',' . $this->editingId : '';
            $rules = [
                'formData.name' => 'required|string|max:255|unique:work_phases,name' . $ignoreId,
                'formData.score_coefficient' => 'nullable|numeric|min:0|max:999999.99',
            ];

            $coefficient = $this->normalizeDecimalInput($this->formData['score_coefficient'] ?? null);
            $this->formData['score_coefficient'] = ($coefficient === null || $coefficient === '') ? 0 : $coefficient;
        } elseif ($this->activeTab === 'NetworkScope') {
            $ignoreId = $this->isEditing && $this->editingId ? ',' . $this->editingId : '';
            $rules = [
                'formData.name' => 'required|string|max:255|unique:network_scopes,name' . $ignoreId,
            ];
        } elseif ($this->activeTab === 'CompanyWorkPhaseRate' || $this->activeTab === 'CompanyDecommissioningRate') {
            return;
        }

        $this->validate($rules);

        $modelClass = $this->getModelClass();
        if (!$modelClass) return;

        // Filter valid fillables
        $instance = new $modelClass();
        $fillables = $instance->getFillable();
        $dataToSave = [];
        foreach ($fillables as $field) {
            // Note: Since 'name' is in $defaultForm, it exists in formData.
            if (array_key_exists($field, $this->formData)) {
                $dataToSave[$field] = $this->formData[$field] === '' ? null : $this->formData[$field];
            }
        }

        if ($this->isEditing && $this->editingId) {
            $record = $modelClass::find($this->editingId);
            if ($record) {
                $record->update($dataToSave);
            }
        } else {
            $modelClass::create($dataToSave);
        }

        $this->forgetLookupCachesFor($modelClass);

        $this->resetModal();
        $this->resetPage();
        session()->flash('message', 'Record salvato con successo!');
    }

    public function deleteRecord($id)
    {
        $modelClass = $this->getModelClass();
        if ($modelClass) {
            $record = $modelClass::find($id);
            if ($record) {
                if ($record instanceof WorkPhase && $record->works()->exists()) {
                    session()->flash('error', 'Impossibile eliminare una fase lavoro già associata a lavorazioni.');
                    return;
                }

                if ($record instanceof NetworkScope && $record->works()->exists()) {
                    session()->flash('error', 'Impossibile eliminare un ambito network già associato a lavorazioni.');
                    return;
                }

                $record->delete();
                $this->forgetLookupCachesFor($modelClass);
                $this->resetPage();
                session()->flash('message', 'Record eliminato!');
            }
        }
    }

    private function getSearchPlaceholder(): string
    {
        return match($this->activeTab) {
            'Central' => 'Cerca centrale o regione...',
            'Comune' => 'Cerca comune, codice, catasto o regione...',
            'Regione' => 'Cerca regione...',
            'Company' => 'Cerca company...',
            'WorkPhase' => 'Cerca fase lavoro...',
            'NetworkScope' => 'Cerca ambito network...',
            'CompanyWorkPhaseRate' => 'Cerca company o fase lavoro...',
            'CompanyDecommissioningRate' => 'Cerca company o voce decommissioning...',
            'ProductionTarget' => '',
            default => 'Cerca...',
        };
    }

    public function saveRate(int $companyId, int $workPhaseId): void
    {
        $value = $this->rateValues[$companyId][$workPhaseId] ?? null;
        $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        validator(
            ['unit_price' => $value],
            ['unit_price' => 'nullable|numeric|min:0|max:99999999.99']
        )->validate();

        if ($value === null || $value === '') {
            CompanyWorkPhaseRate::query()
                ->where('company_id', $companyId)
                ->where('work_phase_id', $workPhaseId)
                ->delete();

            $this->rateValues[$companyId][$workPhaseId] = '';
            session()->flash('message', 'Tariffa rimossa.');

            return;
        }

        $rate = CompanyWorkPhaseRate::updateOrCreate(
            [
                'company_id' => $companyId,
                'work_phase_id' => $workPhaseId,
            ],
            ['unit_price' => round((float) $value, 2)]
        );

        $this->rateValues[$companyId][$workPhaseId] = $rate->unit_price;
        session()->flash('message', 'Tariffa salvata.');
    }

    private function loadRateValues(): void
    {
        $this->rateValues = [];

        $rates = CompanyWorkPhaseRate::query()->get();

        foreach ($rates as $rate) {
            $this->rateValues[$rate->company_id][$rate->work_phase_id] = $rate->unit_price;
        }
    }

    public function saveDecommissioningRate(int $companyId, int $itemIndex): void
    {
        abort_unless(array_key_exists($itemIndex, CompanyDecommissioningRate::ITEM_LABELS), 404);

        $values = $this->decommissioningRateValues[$companyId][$itemIndex] ?? [];
        $progPrice = $this->normalizeDecimalInput($values['prog_price'] ?? null);
        $nePrice = $this->normalizeDecimalInput($values['ne_price'] ?? null);

        validator(
            [
                'prog_price' => $progPrice,
                'ne_price' => $nePrice,
            ],
            [
                'prog_price' => 'nullable|numeric|min:0|max:99999999.99',
                'ne_price' => 'nullable|numeric|min:0|max:99999999.99',
            ]
        )->validate();

        if (($progPrice === null || $progPrice === '') && ($nePrice === null || $nePrice === '')) {
            CompanyDecommissioningRate::query()
                ->where('company_id', $companyId)
                ->where('item_index', $itemIndex)
                ->delete();

            $this->decommissioningRateValues[$companyId][$itemIndex]['prog_price'] = '';
            $this->decommissioningRateValues[$companyId][$itemIndex]['ne_price'] = '';
            session()->flash('message', 'Tariffa decommissioning rimossa.');

            return;
        }

        $rate = CompanyDecommissioningRate::updateOrCreate(
            [
                'company_id' => $companyId,
                'item_index' => $itemIndex,
            ],
            [
                'prog_price' => $progPrice === null || $progPrice === '' ? null : round((float) $progPrice, 2),
                'ne_price' => $nePrice === null || $nePrice === '' ? null : round((float) $nePrice, 2),
            ]
        );

        $this->decommissioningRateValues[$companyId][$itemIndex]['prog_price'] = $rate->prog_price;
        $this->decommissioningRateValues[$companyId][$itemIndex]['ne_price'] = $rate->ne_price;
        session()->flash('message', 'Tariffa decommissioning salvata.');
    }

    private function loadDecommissioningRateValues(): void
    {
        $this->decommissioningRateValues = [];

        $rates = CompanyDecommissioningRate::query()->get();

        foreach ($rates as $rate) {
            $this->decommissioningRateValues[$rate->company_id][$rate->item_index] = [
                'prog_price' => $rate->prog_price,
                'ne_price' => $rate->ne_price,
            ];
        }
    }

    private function loadProductionTargetValues(): void
    {
        $target = ProductionTarget::current();

        $this->productionTargetValues = [
            'monthly_amount_target' => $target->monthly_amount_target,
            'daily_score_target' => $target->daily_score_target,
        ];
    }

    public function saveProductionTarget(): void
    {
        $amount = $this->normalizeDecimalInput($this->productionTargetValues['monthly_amount_target'] ?? null);
        $score = $this->normalizeDecimalInput($this->productionTargetValues['daily_score_target'] ?? null);

        validator(
            [
                'monthly_amount_target' => $amount,
                'daily_score_target' => $score,
            ],
            [
                'monthly_amount_target' => 'required|numeric|min:0|max:99999999.99',
                'daily_score_target' => 'required|numeric|min:0|max:99999999.99',
            ]
        )->validate();

        $target = ProductionTarget::current();
        $target->update([
            'monthly_amount_target' => round((float) $amount, 2),
            'daily_score_target' => round((float) $score, 2),
        ]);

        $this->loadProductionTargetValues();
        session()->flash('message', 'Target produzione salvati.');
    }

    private function normalizeDecimalInput($value)
    {
        return is_string($value) ? str_replace(',', '.', trim($value)) : $value;
    }

    private function forgetLookupCachesFor(string $modelClass): void
    {
        $keys = match ($modelClass) {
            Central::class => ['deco_centrali_list', 'centrali_list'],
            Company::class => ['deco_companies_list'],
            Comune::class => ['deco_comuni_list', 'comuni_list'],
            Regione::class => ['deco_regioni_list', 'regioni_list'],
            default => [],
        };

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    private function applySearch($query)
    {
        $search = trim($this->search);

        if ($search === '') {
            return $query;
        }

        return match($this->activeTab) {
            'Central' => $query->where(function ($q) use ($search) {
                $q->where('central', 'like', "%{$search}%")
                    ->orWhere('region', 'like', "%{$search}%");
            }),
            'Comune' => $query->where(function ($q) use ($search) {
                $q->where('comune_progressive', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('catasto_code', 'like', "%{$search}%")
                    ->orWhere('sovracomune', 'like', "%{$search}%")
                    ->orWhereHas('regione', function ($regionQuery) use ($search) {
                        $regionQuery->where('nome', 'like', "%{$search}%");
                    });
            }),
            'Regione' => $query->where('nome', 'like', "%{$search}%"),
            'Company' => $query->where('name', 'like', "%{$search}%"),
            'WorkPhase' => $query->where('name', 'like', "%{$search}%"),
            'NetworkScope' => $query->where('name', 'like', "%{$search}%"),
            default => $query,
        };
    }

    public function render()
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass ? $modelClass::query() : null;

        if ($this->activeTab === 'CompanyWorkPhaseRate') {
            return view('livewire.admin-base-tables', [
                'records' => collect([]),
                'regioni' => Regione::orderBy('nome')->pluck('nome', 'id'),
                'searchPlaceholder' => $this->getSearchPlaceholder(),
                'companiesForRates' => Company::orderBy('name')->get(),
                'workPhasesForRates' => WorkPhase::orderBy('name')->get(),
                'decommissioningRateItems' => [],
            ]);
        }

        if ($this->activeTab === 'CompanyDecommissioningRate') {
            return view('livewire.admin-base-tables', [
                'records' => collect([]),
                'regioni' => Regione::orderBy('nome')->pluck('nome', 'id'),
                'searchPlaceholder' => $this->getSearchPlaceholder(),
                'companiesForRates' => Company::orderBy('name')->get(),
                'workPhasesForRates' => collect([]),
                'decommissioningRateItems' => CompanyDecommissioningRate::ITEM_LABELS,
            ]);
        }

        if ($this->activeTab === 'ProductionTarget') {
            if ($this->productionTargetValues === []) {
                $this->loadProductionTargetValues();
            }

            return view('livewire.admin-base-tables', [
                'records' => collect([]),
                'regioni' => collect([]),
                'searchPlaceholder' => $this->getSearchPlaceholder(),
                'companiesForRates' => collect([]),
                'workPhasesForRates' => collect([]),
                'decommissioningRateItems' => [],
            ]);
        }

        if ($this->activeTab === 'Comune' && $query) {
            $query->with('regione');
        }

        $records = $query
            ? $this->applySearch($query)->orderBy('id')->paginate(self::PER_PAGE)
            : collect([]);

        return view('livewire.admin-base-tables', [
            'records' => $records,
            'regioni' => Regione::orderBy('nome')->pluck('nome', 'id'),
            'searchPlaceholder' => $this->getSearchPlaceholder(),
            'companiesForRates' => collect([]),
            'workPhasesForRates' => collect([]),
            'decommissioningRateItems' => [],
        ]);
    }
}
