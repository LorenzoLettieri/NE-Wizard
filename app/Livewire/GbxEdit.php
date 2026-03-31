<?php

namespace App\Livewire;

use App\Models\Gbx;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesPdfUploads;

class GbxEdit extends Component
{
    use WithFileUploads;
    use HandlesPdfUploads;

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

    #[On('edit-gbx')]
    public function editGbx($id)
    {
        $this->gbx = Gbx::with('media')->find($id);
        $this->fill($this->gbx->toArray());
        $this->files = [];
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();
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
        ] + $this->pdfUploadValidationRules());

        $this->gbx->update($this->except([
            'centrals',
            'companies',
            'gbx',
            'files',
            'uploadMessage',
            'uploadMessageType',
        ]));

        $uploadedCount = 0;
        if ($this->files) {
            $uploadedCount = $this->persistUploadedFiles($this->gbx, 'gbx_media');
        }

        $removedCount = $this->commitPendingMediaRemovals($this->gbx);
        $this->gbx->refresh();
        $message = 'GBX aggiornato con successo!';

        if ($uploadedCount > 0 || $removedCount > 0) {
            $details = [];

            if ($uploadedCount > 0) {
                $details[] = $uploadedCount === 1 ? '1 allegato caricato' : "{$uploadedCount} allegati caricati";
            }

            if ($removedCount > 0) {
                $details[] = $removedCount === 1 ? '1 allegato rimosso' : "{$removedCount} allegati rimossi";
            }

            $message .= ' ' . implode(', ', $details) . '.';
        }

        session()->flash('success', $message);
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
