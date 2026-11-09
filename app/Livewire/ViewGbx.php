<?php
namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\Gbx;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\On;

class ViewGbx extends Component
{
    use AuthorizesComponentAccess;
    use AuthorizesRequests;

    public $gbx;

    protected function canAccessComponent(User $user): bool
    {
        return $user->can(PermissionCatalog::GBX_ACCESS);
    }

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
