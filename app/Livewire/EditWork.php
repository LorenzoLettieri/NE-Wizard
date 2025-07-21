<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Carbon;

class EditWork extends Component
{
    
    public $work;
    public $operators;

    public $companies;
    public $centrals;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number,$unica_number, $notes;

    #[On('edit-work')] 
     public function editWork($id){
        $this->work = Work::find($id);

        $this->company_id = $this->work->company_id;
        $this->central_id = $this->work->central_id;
        $this->operator_id = $this->work->users->pluck('id')->toArray();
        $this->status = $this->work->status;
        $this->network = $this->work->network;
        $this->ao_cno = $this->work->ao_cno;
        $this->ntw_scope = $this->work->ntw_scope;
        $this->description = $this->work->description;
        $this->type = $this->work->type;
        $this->phase = $this->work->phase;
        $this->company_assistant = $this->work->company_assistant;
        $this->nroe = $this->work->nroe;
        $this->wo_number = $this->work->wo_number;
        $this->unica_number = $this->work->unica_number;
        $this->notes = $this->work->notes;

        $this->suspension_history = $this->work->suspension_history;
     }

     public function update(){
        $this->work->update( $this->all());
        $this->work->users()->detach();
        $this->work->users()->attach($this->operator_id, ['created_at' => Carbon::now()]);

        session()->flash('success', 'Lavorazione aggiornata con successo!');
        $this->dispatch('workUpdated');
    }

     public function mount(){
        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->operators = User::permission('get works')->get();
     }

    public function render()
    {
        return view('livewire.edit-work');
    }
}
