<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Central;
use App\Models\Comune;
use App\Models\Regione;
use App\Models\Company;

class AdminBaseTables extends Component
{
    public $activeTab = 'Central';
    
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
        'code' => '',
        'name' => '',
        'location' => '',
        'sovracomune' => '',
        'catasto_code' => '',
        'nome' => '',
    ];

    public function mount()
    {
        $this->resetForm();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetModal();
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
            default => null,
        };
    }

    public function saveRecord()
    {
        // Add basic validation depending on the tab
        $rules = [];
        if ($this->activeTab === 'Central') {
            $rules = [
                'formData.central' => 'required|string|max:255',
                'formData.region' => 'nullable|string|max:255',
            ];
        } elseif ($this->activeTab === 'Comune') {
            $rules = [
                'formData.name' => 'required|string|max:255',
                'formData.code' => 'nullable|string|max:255',
                'formData.location' => 'nullable|string|max:255',
                'formData.region' => 'nullable|string|max:255',
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
                $dataToSave[$field] = $this->formData[$field] ?: null;
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
        session()->flash('message', 'Record salvato con successo!');
    }

    public function deleteRecord($id)
    {
        $modelClass = $this->getModelClass();
        if ($modelClass) {
            $record = $modelClass::find($id);
            if ($record) {
                $record->delete();
                session()->flash('message', 'Record eliminato!');
            }
        }
    }

    public function render()
    {
        $modelClass = $this->getModelClass();
        $records = $modelClass ? $modelClass::all() : collect([]);

        return view('livewire.admin-base-tables', [
            'records' => $records
        ]);
    }
}
