<div class="row g-4">
    <div class="col-12 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header fw-bold">Ruoli</div>
            <ul class="list-group list-group-flush">
                @foreach ($roles as $role)
                    <li class="list-group-item d-flex justify-content-between align-items-center {{ $selectedRoleId === $role->id ? 'active' : '' }}">
                        <button type="button" class="btn btn-link p-0 text-start text-decoration-none {{ $selectedRoleId === $role->id ? 'text-white' : '' }}"
                            wire:click="selectRole({{ $role->id }})">
                            {{ $role->name }}
                            @if ($role->name === $superAdminRole)
                                <span class="badge text-bg-dark ms-1">super admin</span>
                            @endif
                        </button>
                        <span class="d-flex align-items-center gap-2">
                            <span class="badge rounded-pill text-bg-secondary" title="Utenti con questo ruolo">{{ $role->users_count }}</span>
                            @if ($role->name !== $superAdminRole && $role->users_count === 0)
                                <button type="button" class="btn btn-sm btn-outline-danger" title="Elimina ruolo"
                                    wire:click="deleteRole({{ $role->id }})"
                                    wire:confirm="Eliminare il ruolo {{ $role->name }}?">
                                    <i class="bi bi-trash"></i>
                                </button>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ul>
            <div class="card-body border-top">
                <form wire:submit="createRole">
                    <label class="form-label" for="newRoleName">Nuovo ruolo</label>
                    <div class="input-group">
                        <input type="text" id="newRoleName" class="form-control @error('newRoleName') is-invalid @enderror"
                            wire:model="newRoleName" maxlength="60" placeholder="Es. Responsabile Deco">
                        <button type="submit" class="btn btn-success"><i class="bi bi-plus"></i> Crea</button>
                    </div>
                    @error('newRoleName') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-8">
        @if ($successMessage)
            <div class="alert alert-success">{{ $successMessage }}</div>
        @endif
        @error('delete') <div class="alert alert-danger">{{ $message }}</div> @enderror

        @if (! $selectedRole)
            <div class="alert alert-secondary">Seleziona un ruolo per vederne o modificarne i permessi.</div>
        @elseif ($isSuperAdminRole)
            <div class="alert alert-info">
                Il ruolo <strong>{{ $selectedRole->name }}</strong> è il super admin: ha sempre tutti i permessi,
                gestisce utenti e ruoli e non si modifica da qui.
            </div>
        @else
            <form wire:submit="savePermissions">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="mb-0">Permessi di <strong>{{ $selectedRole->name }}</strong></h4>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salva permessi</button>
                </div>
                <p class="text-muted small">
                    I permessi di contabilità sono separati per modulo. Se scegli un permesso che ne richiede un altro
                    (per esempio "modifica contabilità" richiede "vede contabilità"), quello necessario viene aggiunto al salvataggio.
                    I permessi segnati come <span class="badge text-bg-light border">persona</span> descrivono cosa fa chi ha il ruolo
                    (timbrare, essere assegnabile) e non vengono dati automaticamente al super admin.
                </p>

                @foreach ($groups as $groupName => $permissions)
                    <div class="card mb-3 shadow-sm">
                        <div class="card-header fw-bold">{{ $groupName }}</div>
                        <div class="card-body">
                            @foreach ($permissions as $permission => $label)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="{{ $permission }}"
                                        id="perm-{{ $loop->parent->index }}-{{ $loop->index }}" wire:model="selectedPermissions">
                                    <label class="form-check-label" for="perm-{{ $loop->parent->index }}-{{ $loop->index }}">
                                        {{ $label }}
                                        <code class="small text-muted ms-1">{{ $permission }}</code>
                                        @if (in_array($permission, $personal, true))
                                            <span class="badge text-bg-light border ms-1">persona</span>
                                        @endif
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Salva permessi</button>
                </div>
            </form>
        @endif
    </div>
</div>
