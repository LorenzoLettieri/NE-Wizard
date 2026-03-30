<?php

namespace App\Livewire;

use App\Models\Gbx;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;

class GbxEdit extends Component
{
    use WithFileUploads;
    public $gbx;
    public $centrals, $companies;
    public $company_id, $network, $SDF, $central_id, $comune, $client, $coordinates;
    public $appointment_date, $inspection_date, $verbal_date, $obligation_date, $release_date, $project_date,
    $speedark_date;
    public $permission_request_date, $permission_obtain_date, $cart_update_date;
    public $date;
    public $is_adeguate, $permissions, $CO_advancement;
    public $value, $company_paid, $bezzi_paid, $project_paid, $dl_paid;
    public $inspection_notes, $permission_notes, $project_notes, $client_notes;
    public $files = [];

    #[On('edit-gbx')]
    public function editGbx($id)
    {
        $this->gbx = Gbx::find($id);
        $this->fill($this->gbx->toArray());
    }

    public function update()
    {
        $this->validate([
            'central_id' => 'nullable|exists:centrals,id',
            'company_id' => 'nullable|exists:companies,id',
            'value' => 'nullable|numeric',
            'company_paid' => 'nullable|numeric',
            'bezzi_paid' => 'nullable|numeric',
            'project_paid' => 'nullable|numeric',
            'dl_paid' => 'nullable|numeric',
            'date' => 'nullable|date',
            'files' => 'nullable|array',
            'files.*' => 'file|mimes:pdf|max:10240',
        ]);

        $this->gbx->update($this->except(['centrals', 'companies', 'gbx', 'files']));

        if ($this->files) {
            foreach ($this->files as $file) {
                $path = $file->store('gbx_media', 'public');
                $this->gbx->media()->create([
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }
            $this->files = []; // Clear files after upload
        }

        session()->flash('success', 'GBX aggiornato con successo!');
        $this->dispatch('gbxUpdated');
    }

    public function mount()
    {
        $this->centrals = Central::all();
        $this->companies = Company::all();
    }

    public function render()
    {
        return view('livewire.gbx-edit');
    }
}
