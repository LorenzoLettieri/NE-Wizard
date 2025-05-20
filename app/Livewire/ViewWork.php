<?php

namespace App\Livewire;

use App\Models\Work;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewWork extends Component
{
    public $work;
    public $operators;

     #[On('view-work')] 
     public function viewWork($id){
        $this->work = Work::find($id);
        $this->operators = $this->work->users->pluck('name')->join(' | ');
     }

    public function render()
    {
        return view('livewire.view-work');
    }
}
