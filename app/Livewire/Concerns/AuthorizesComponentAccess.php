<?php

namespace App\Livewire\Concerns;

use App\Models\User;

/**
 * Controllo di accesso di base del componente, ripetuto a ogni richiesta Livewire.
 *
 * Le azioni Livewire si possono chiamare direttamente dal browser: il permesso della pagina
 * non basta, quindi il componente lo verifica da sé dopo mount/hydrate e prima di ogni azione.
 */
trait AuthorizesComponentAccess
{
    abstract protected function canAccessComponent(User $user): bool;

    public function bootedAuthorizesComponentAccess(): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User && $this->canAccessComponent($user), 403);
    }
}
