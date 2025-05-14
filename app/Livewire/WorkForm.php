<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Carbon\Carbon;
use Livewire\Component;

class WorkForm extends Component
{
    public $operators;
    public $companies, $centrals;

    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number,$unica_number, $notes;


    public function store(){

        $work = Work::create($this->except(['companies']));
        $work->users()->attach($this->operator_id, ['created_at' => Carbon::now()]);
        session()->flash('success','Lavorazione Aggiunta con successo!');

        $this->redirect(route('addWork'));
    }
    
    public function mount(){
        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->operators = User::permission('get works')->get();
    }

    public function render()
    {
        return view('livewire.work-form');
    }
}
