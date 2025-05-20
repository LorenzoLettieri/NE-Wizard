<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Illuminate\Support\Carbon;

class WorkEdit extends Component
{
    public $work;
    public $operators;
    public $companies, $centrals;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number,$unica_number, $notes;

    public function update(){
        $this->work->update( $this->all());
        $this->work->users()->detach();
        $this->work->users()->attach($this->operator_id, ['created_at' => Carbon::now()]);

        session()->flash('success', 'Lavorazione aggiornata con successo!');
        $this->redirect(route('works-table'));
    }

    public function mount(Work $work){
        $this->work = $work;

        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->operators = User::permission('get works')->get();

        $this->company_id = $work->company_id;
        $this->central_id = $work->central_id;
        $this->operator_id = $work->users->pluck('id')->toArray();
        $this->status = $work->status;
        $this->network = $work->network;
        $this->ao_cno = $work->ao_cno;
        $this->ntw_scope = $work->ntw_scope;
        $this->description = $work->description;
        $this->type = $work->type;
        $this->phase = $work->phase;
        $this->company_assistant = $work->company_assistant;
        $this->nroe = $work->nroe;
        $this->wo_number = $work->wo_number;
        $this->unica_number = $work->unica_number;
        $this->notes = $work->notes;

        $this->suspension_history = $work->suspension_history;

    }
    public function render()
    {
        return view('livewire.work-edit');
    }
}
