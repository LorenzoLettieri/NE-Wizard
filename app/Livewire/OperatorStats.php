<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;

class OperatorStats extends Component
{
    public $startDate;
    public $endDate;

    public function mount(){
        $this->startDate = Carbon::now()->subDays(31)->toDateString();
        $this->endDate = Carbon::now()->add(1,'day')->toDateString();

    }
    public function render()
    {
        
        $operators = User::permission('get works')->with(['works' => function ($query){
            $query->whereBetween('user_work.created_at', [$this->startDate, $this->endDate]);
        }])->get();
        return view('livewire.operator-stats', compact('operators'));
    }
}
