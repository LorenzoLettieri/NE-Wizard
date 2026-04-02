<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;

class WorkForm extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;

    public $operators;
    public $companies, $centrals;

    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne, $expected_delivery_date;

    public $go_live, $date_in_str, $date_out_str;

    public bool $daphne;

    public function store()
    {
        $this->validate($this->rules());

        $work = Work::create($this->workPayload());
        $work->users()->attach($this->operator_id, ['created_at' => Carbon::now()]);

        if ($this->files) {
            $this->persistUploadedFiles($work, 'works_media');
        }

        session()->flash('success', 'Lavorazione Aggiunta con successo!');

        $this->redirect(route('addWork'));
    }

    public function mount()
    {
        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->operators = User::permission('get works')->get();
    }

    protected function rules(): array
    {
        return array_merge($this->mediaUploadValidationRules(), [
            'expected_delivery_date' => 'nullable|date',
        ]);
    }

    protected function workPayload(): array
    {
        return [
            'company_id' => $this->company_id,
            'central_id' => $this->central_id,
            'status' => $this->status,
            'network' => $this->network,
            'ao_cno' => $this->ao_cno,
            'ntw_scope' => $this->ntw_scope,
            'description' => $this->description,
            'type' => $this->type,
            'phase' => $this->phase,
            'daphne' => $this->daphne,
            'tempo_daphne' => $this->tempo_daphne,
            'company_assistant' => $this->company_assistant,
            'nroe' => $this->nroe,
            'wo_number' => $this->wo_number,
            'unica_number' => $this->unica_number,
            'go_live' => $this->go_live,
            'date_in_str' => $this->date_in_str,
            'date_out_str' => $this->date_out_str,
            'notes' => $this->notes,
            'expected_delivery_date' => $this->expected_delivery_date,
        ];
    }

    public function render()
    {
        return view('livewire.work-form');
    }
}
