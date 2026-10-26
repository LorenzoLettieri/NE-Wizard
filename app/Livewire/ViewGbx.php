<?php
namespace App\Livewire;

use App\Models\Gbx;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewGbx extends Component
{
    use AuthorizesRequests;

    public $gbx;

    #[On('view-gbx')]
    public function viewGbx($id)
    {
        $gbx = Gbx::with(['central', 'company'])->findOrFail($id);
        $this->authorize('view', $gbx);

        $this->gbx = $gbx;
    }

    public function render()
    {
        return view('livewire.view-gbx');
    }
}
