<?php

namespace App\Livewire;

use App\Models\Gbx;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;

class GbxForm extends Component
{
    use WithFileUploads;

    public $centrals, $companies;
    public $company_id, $network, $SDF, $central_id, $comune, $client, $coordinates;
    public $appointment_date, $inspection_date, $verbal_date, $obligation_date, $release_date, $project_date,
    $speedark_date;
    public $permission_request_date, $permission_obtain_date, $cart_update_date;
    public $date;
    public $is_adeguate = 0, $permissions = 0, $CO_advancement = 0;
    public $value, $company_paid, $bezzi_paid, $project_paid, $dl_paid;
    public $inspection_notes, $permission_notes, $project_notes, $client_notes;
    public $files = [];

    public function mount()
    {
        $this->centrals = Central::all();
        $this->companies = Company::all();
        $this->date = date('Y-m-d');
    }

    public function store()
    {
        $validated = $this->validate([
            'central_id' => 'nullable|exists:centrals,id',
            'company_id' => 'nullable|exists:companies,id',
            'value' => 'nullable|numeric',
            'company_paid' => 'nullable|numeric',
            'bezzi_paid' => 'nullable|numeric',
            'project_paid' => 'nullable|numeric',
            'dl_paid' => 'nullable|numeric',
            'date' => 'nullable|date',
        ]);

        $gbx = Gbx::create($this->except(['centrals', 'companies', 'files']));

        if ($this->files) {
            foreach ($this->files as $file) {
                $path = $file->store('gbx_media', 'public');
                $gbx->media()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
        }

        session()->flash('success', 'GBX aggiunto con successo!');
        $this->redirect(route('gbxes-table'));
    }

    public function render()
    {
        return view('livewire.gbx-form');
    }
}