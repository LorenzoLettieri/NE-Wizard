<?php
namespace App\Livewire;

use App\Models\Gbx;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewGbx extends Component
{
    public $gbx;

    #[On('view-gbx')]
    public function viewGbx($id)
    {
        $this->gbx = Gbx::with(['central', 'company'])->find($id);
    }

    public function render()
    {
        return view('livewire.view-gbx');
    }
}