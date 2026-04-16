<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

    public function canManageAllWorks(): bool
    {
        return $this->hasAnyRole(['admin', 'supervisor']);
    }

    public function isAssignedToWork(Work $work): bool
    {
        if ($work->relationLoaded('users')) {
            return $work->users->contains(fn (User $user) => $user->is($this));
        }

        return $this->works()->whereKey($work->getKey())->exists();
    }
}
