<?php

namespace App\Livewire;

use App\Models\User;
use Carbon\Carbon;
use Livewire\Component;

class OperatorStats extends Component
{
    public $startDate;
    public $endDate;

    public function mount()
    {
        $this->startDate = Carbon::now()->subDays(31)->toDateString();
        $this->endDate = Carbon::now()->toDateString();
    }

    public function render()
    {
        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        $operators = User::permission('get works')
            ->with(['works' => function ($query) use ($startDate, $endDate) {
                $query->whereBetween('user_work.created_at', [$startDate, $endDate])
                    ->with('workSuspensions');
            }])
            ->get();

        return view('livewire.operator-stats', compact('operators'));
    }
}
