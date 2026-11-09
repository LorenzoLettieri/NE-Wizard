<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\Authorization\PermissionCatalog;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function works()
    {
        return $this->belongsToMany(Work::class);
    }

    public function permessiEnte()
    {
        return $this->belongsToMany(PermessoEnte::class, 'permesso_ente_user');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function decommissionings()
    {
        return $this->hasMany(Decommissioning::class, 'progettista_id');
    }

    /**
     * Super admin: gestione utenti, ruoli e Adminer. Non è un permesso, così nessun ruolo può ottenerlo.
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE);
    }

    public function hasAllAbilities(string ...$permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->can($permission)) {
                return false;
            }
        }

        return true;
    }

    // Lavorazioni

    public function canViewAllWorks(): bool
    {
        return $this->can(PermissionCatalog::WORKS_VIEW_ALL);
    }

    public function canManageWorks(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::WORKS_MANAGE, PermissionCatalog::WORKS_VIEW_ALL);
    }

    public function canViewWorksFinance(): bool
    {
        return $this->can(PermissionCatalog::WORKS_FINANCE_VIEW);
    }

    public function isWorksOperator(): bool
    {
        return $this->can(PermissionCatalog::WORKS_OPERATE);
    }

    public function isAssignedToWork(Work $work): bool
    {
        if ($work->relationLoaded('users')) {
            return $work->users->contains(fn (User $user) => $user->is($this));
        }

        return $this->works()->whereKey($work->getKey())->exists();
    }

    // Permessi Ente

    public function canViewAllPermessiEnte(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::PERMESSI_ACCESS, PermissionCatalog::PERMESSI_VIEW_ALL);
    }

    public function canManagePermessiEnte(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::PERMESSI_ACCESS, PermissionCatalog::PERMESSI_MANAGE);
    }

    public function canViewPermessiEnteFinance(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::PERMESSI_ACCESS, PermissionCatalog::PERMESSI_FINANCE_VIEW);
    }

    public function canEditPermessiEnteFinance(): bool
    {
        return $this->canViewPermessiEnteFinance() && $this->can(PermissionCatalog::PERMESSI_FINANCE_EDIT);
    }

    public function isAssignedToPermessoEnte(PermessoEnte $permesso): bool
    {
        if ($permesso->relationLoaded('users')) {
            return $permesso->users->contains(fn (User $user) => $user->is($this));
        }

        return $this->permessiEnte()->whereKey($permesso->getKey())->exists();
    }

    // GBX

    public function canViewAllGbx(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::GBX_ACCESS, PermissionCatalog::GBX_VIEW_ALL);
    }

    public function canViewGbxFinance(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::GBX_ACCESS, PermissionCatalog::GBX_FINANCE_VIEW);
    }

    public function canEditGbxFinance(): bool
    {
        return $this->canViewGbxFinance() && $this->can(PermissionCatalog::GBX_FINANCE_EDIT);
    }

    public function belongsToGbxCompany(Gbx $gbx): bool
    {
        // Confronto per nome come il filtro della tabella GBX, così le due regole non divergono
        $companyName = $this->company?->name;

        return $companyName !== null && $gbx->company?->name === $companyName;
    }

    // Decommissioning

    public function canViewAllDecommissionings(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::DECO_ACCESS, PermissionCatalog::DECO_VIEW_ALL);
    }

    public function canViewDecommissioningFinance(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::DECO_ACCESS, PermissionCatalog::DECO_FINANCE_VIEW);
    }

    public function canEditDecommissioningFinance(): bool
    {
        // Chi modifica gli importi deve anche poterli vedere
        return $this->canViewDecommissioningFinance()
            && $this->can(PermissionCatalog::DECO_FINANCE_EDIT);
    }

    public function canViewDecommissioningReport(): bool
    {
        // Il report è fatto di totali economici
        return $this->can(PermissionCatalog::DECO_REPORT) && $this->canViewDecommissioningFinance();
    }

    public function isAssignedToDecommissioning(Decommissioning $decommissioning): bool
    {
        return $decommissioning->progettista_id !== null
            && (int) $decommissioning->progettista_id === (int) $this->getKey();
    }

    // Report e presenze

    public function canViewOperatorReports(): bool
    {
        return $this->can(PermissionCatalog::REPORTS_OPERATORS);
    }

    public function canViewOperatorActivity(): bool
    {
        return $this->hasAllAbilities(PermissionCatalog::REPORTS_OPERATORS, PermissionCatalog::REPORTS_OPERATORS_ACTIVITY);
    }

    public function canAccessBaseTables(): bool
    {
        return $this->can(PermissionCatalog::SETTINGS_BASE_TABLES) || $this->can(PermissionCatalog::SETTINGS_RATES);
    }
}
