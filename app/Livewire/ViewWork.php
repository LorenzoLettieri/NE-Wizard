<?php

namespace App\Livewire;

use App\Models\Work;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewWork extends Component
{
    use AuthorizesRequests;

    public $work;
    public $operators;

     #[On('view-work')] 
     public function viewWork($id){
        $this->work = Work::with(['company', 'central', 'users', 'media', 'workSuspensions'])->findOrFail($id);
        $this->authorize('view', $this->work);
        $this->operators = $this->work->users->pluck('name')->join(' | ');
     }

     #[On('duplicate-work')] 
     public function duplicateWork($id){
        $work = Work::with('users')->findOrFail($id);
        $this->authorize('duplicate', $work);
        $assOperators = $work->users->pluck('id');
        $newWork = $work->replicate();
        $newWork->status = 'Da Lavorare';
        $newWork->acception_date = null;
        $newWork->delivery_date = null;
        $newWork->completion_date = null;
        $newWork->expected_delivery_date = null;
        $newWork->suspension_history = null;
        $newWork->save();

        if ($assOperators->isNotEmpty()) {
            $now = Carbon::now();
            $newWork->users()->attach($assOperators->mapWithKeys(fn (int $operatorId): array => [
                $operatorId => [
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ])->all());
        }


        return redirect(route("editWork", $newWork->id))->with('message', 'Hai duplicato la lavorazione, puoi modificarla da qui:');
     }
     #[On('end-work')] 
     public function endWork($id){
        $work = Work::findOrFail($id);
        $this->authorize('complete', $work);
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
