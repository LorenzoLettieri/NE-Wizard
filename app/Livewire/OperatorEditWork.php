<?php

namespace App\Livewire;

use App\Models\Work;
use Livewire\Component;
use Livewire\Attributes\On;

class OperatorEditWork extends Component
{
    public $work;

    public $suspension_history;
    public $notes;

    #[On('edit-work')] 
     public function editWork($id){
        $this->work = Work::find($id);

        $this->notes = $this->work->notes;

        $this->suspension_history = $this->work->suspension_history;
     }

     public function update(){
        $this->work->update( $this->all());

        session()->flash('success', 'Lavorazione aggiornata con successo!');
        $this->dispatch('workUpdated');
    }

    public function render()
    {
        return view('livewire.operator-edit-work');
    }
}
