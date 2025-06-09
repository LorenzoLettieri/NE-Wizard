<?php

namespace App\Livewire;

use App\Models\Work;
use Carbon\Carbon;
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

     #[On('duplicate-work')] 
     public function duplicateWork($id){
        $work = Work::find($id);
        $assOperators = $work->users->pluck('id');
        $newWork = $work->replicate();
        $newWork->save();
        $newWork->users()->attach($assOperators);


        return redirect(route("editWork", $newWork->id))->with('message', 'Hai duplicato la lavorazione, puoi modificarla da qui:');
     }
     #[On('end-work')] 
     public function endWork($id){
        $work = Work::find($id);
        $work->status = "Fine Lavori";
        $work->completion_date = Carbon::now();
        $work->save();

        $this->dispatch('refreshDatatable');
     }

    public function render()
    {
        return view('livewire.view-work');
    }
}
