<?php

namespace App\Livewire;

use App\Models\Gbx;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;

class GbxForm extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;

    public $centrals, $companies;
    public $company_id, $network, $SDF, $central_id, $comune, $client, $coordinates;
    public $appointment_date, $inspection_date, $verbal_date, $obligation_date, $release_date, $project_date,
    $speedark_date;
    public $permission_request_date, $permission_obtain_date, $cart_update_date;
    public $date;
    public $is_adeguate = 0, $permissions = 0, $CO_advancement = 0;
    public $value, $company_paid, $bezzi_paid, $project_paid, $dl_paid;
    public $inspection_notes, $permission_notes, $project_notes, $client_notes;

    public function mount()
    {
        $this->centrals = Central::all();
        $this->companies = Company::all();
        $this->date = date('Y-m-d');
        $this->initializeChunkedMediaUploads('gbx');
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
        ] + $this->mediaUploadValidationRules());

        $gbx = Gbx::create($this->except([
            'centrals',
            'companies',
            'files',
            'uploadMessage',
            'uploadMessageType',
            'mediaUploadContext',
            'mediaUploadModelId',
            'mediaUploadFormToken',
            'completedUploadSessionIds',
        ]));

        if ($this->files) {
            $this->persistUploadedFiles($gbx, 'gbx_media');
        }

        $this->claimCompletedUploadSessions($gbx);

        session()->flash('success', 'GBX aggiunto con successo!');
        $this->redirect(route('gbxes-table'));
    }

    public function render()
    {
        return view('livewire.gbx-form');
    }
}
