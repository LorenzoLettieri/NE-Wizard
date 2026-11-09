<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Gestione ruoli e permessi: riservata al super admin.
 *
 * Il ruolo super admin non si modifica né si elimina da qui: riceve sempre tutti i permessi del catalogo.
 */
class RoleManager extends Component
{
    use AuthorizesComponentAccess;

    #[Locked]
    public ?int $selectedRoleId = null;

    /** @var list<string> */
    public array $selectedPermissions = [];

    public string $newRoleName = '';

    public ?string $successMessage = null;

    protected function canAccessComponent(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function selectRole(int $roleId): void
    {
        $role = Role::where('guard_name', 'web')->findOrFail($roleId);

        $this->selectedRoleId = $role->id;
        $this->selectedPermissions = $role->permissions()->pluck('name')->intersect(PermissionCatalog::all())->values()->all();
        $this->successMessage = null;
        $this->resetValidation();
    }

    public function createRole(): void
    {
        $this->newRoleName = trim($this->newRoleName);

        $this->validate([
            'newRoleName' => [
                'required',
                'string',
                'max:60',
                Rule::unique('roles', 'name')->where('guard_name', 'web'),
                Rule::notIn([PermissionCatalog::SUPER_ADMIN_ROLE]),
            ],
        ], [
            'newRoleName.required' => 'Inserisci il nome del ruolo.',
            'newRoleName.unique' => 'Esiste già un ruolo con questo nome.',
            'newRoleName.not_in' => 'Questo nome è riservato.',
        ]);

        $role = Role::create(['name' => $this->newRoleName, 'guard_name' => 'web']);
        $this->newRoleName = '';
        $this->selectRole($role->id);
        $this->successMessage = "Ruolo \"{$role->name}\" creato: scegli i permessi e salva.";
    }

    public function savePermissions(): void
    {
        $role = $this->editableRole();

        // Solo permessi del catalogo, completati con quelli che implicano (es. modifica => visione)
        $requested = array_values(array_filter($this->selectedPermissions, 'is_string'));
        $permissions = PermissionCatalog::withImplied($requested);
        $added = array_diff($permissions, $requested);

        DB::transaction(fn () => $role->syncPermissions($permissions));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->selectedPermissions = $permissions;
        $this->successMessage = 'Permessi salvati.'
            .($added !== [] ? ' Aggiunti automaticamente perché necessari: '.implode(', ', array_map(
                fn (string $name) => PermissionCatalog::label($name) ?? $name,
                $added
            )).'.' : '');
    }

    public function deleteRole(int $roleId): void
    {
        $role = Role::where('guard_name', 'web')->findOrFail($roleId);

        abort_if($role->name === PermissionCatalog::SUPER_ADMIN_ROLE, 403);

        if ($role->users()->exists()) {
            $this->addError('delete', "Il ruolo \"{$role->name}\" è assegnato ad almeno un utente: toglilo prima dagli utenti.");

            return;
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if ($this->selectedRoleId === $roleId) {
            $this->selectedRoleId = null;
            $this->selectedPermissions = [];
        }

        $this->successMessage = 'Ruolo eliminato.';
    }

    private function editableRole(): Role
    {
        abort_if($this->selectedRoleId === null, 404);

        $role = Role::where('guard_name', 'web')->findOrFail($this->selectedRoleId);

        abort_if($role->name === PermissionCatalog::SUPER_ADMIN_ROLE, 403);

        return $role;
    }

    public function render()
    {
        $roles = Role::where('guard_name', 'web')->withCount('users')->orderBy('name')->get();
        $selectedRole = $this->selectedRoleId ? $roles->firstWhere('id', $this->selectedRoleId) : null;

        return view('livewire.role-manager', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'isSuperAdminRole' => $selectedRole?->name === PermissionCatalog::SUPER_ADMIN_ROLE,
            'groups' => PermissionCatalog::groups(),
            'personal' => PermissionCatalog::personal(),
            'superAdminRole' => PermissionCatalog::SUPER_ADMIN_ROLE,
        ]);
    }
}
