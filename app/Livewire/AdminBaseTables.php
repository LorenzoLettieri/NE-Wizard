<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Central;
use App\Models\Comune;
use App\Models\Regione;
use App\Models\Company;
use App\Models\WorkPhase;

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
            ];
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

                $record->delete();
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
            default => 'Cerca...',
        };
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
            default => $query,
        };
    }

    public function render()
    {
        $modelClass = $this->getModelClass();
        $query = $modelClass ? $modelClass::query() : null;

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
        ]);
    }
}
