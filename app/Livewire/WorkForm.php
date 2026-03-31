<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesPdfUploads;

class WorkForm extends Component
{
    use WithFileUploads;
    use HandlesPdfUploads;

    public $operators;
    public $companies, $centrals;

    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne;

    public $go_live, $date_in_str, $date_out_str;

    public bool $daphne;

    public function store()
    {
        $this->validate($this->pdfUploadValidationRules());

        $work = Work::create($this->except([
            'companies',
            'centrals',
            'operators',
            'files',
            'uploadMessage',
            'uploadMessageType',
        ]));
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

    public function render()
    {
        return view('livewire.work-form');
    }
}
