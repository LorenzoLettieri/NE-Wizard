<?php

namespace App\Livewire;

use App\Models\Work;
use App\Models\Company;
use Livewire\Component;

class WorkForm extends Component
{

    public $companies;

    public $company_id, $status, $network, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number,$unica_number, $notes;


    public function store(){
        $work = Work::create($this->except(['companies']));

        session()->flash('success','Lavorazione Aggiunta con successo!');

        $this->redirect(route('addWork'));
    }
    public function mount(){
        $this->companies = Company::all();

    }
    public function render()
    {
        return view('livewire.work-form');
    }
}
