<div data-bs-boundary="window" class="dropdown">
    <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-pencil"></i>
    </a>

    <ul class="dropdown-menu shadow">
        <li>
            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal"
                onclick="Livewire.dispatch('view-decommissioning', {id: {{ $row->id }}})">
                <i class="bi bi-eye me-2"></i> Dettaglio
            </a>
        </li>
        <li>
            <a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#editModal"
                onclick="Livewire.dispatch('edit-decommissioning', {id: {{ $row->id }}})">
                <i class="bi bi-pencil me-2"></i> Modifica
            </a>
        </li>

        @role('admin')
            <li><hr class="dropdown-divider"></li>
            <li>
                <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteDecommissioningModal{{ $row->id }}">
                    <i class="bi bi-trash me-2"></i> Elimina
                </a>
            </li>
        @endrole
    </ul>

    @role('admin')
        <div class="modal fade" id="deleteDecommissioningModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5">Sicuro di voler eliminare questo Decommissioning?</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                        <form method="POST" action="{{ route('deleteDecommissioning', ['decommissioning' => $row]) }}">
                            @method('DELETE')
                            @csrf
                            <button type="submit" class="btn btn-danger">Si, elimina</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endrole
</div>
