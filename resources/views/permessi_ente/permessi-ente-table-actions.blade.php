@php
  $isDelivered = in_array($row->status, \App\Models\PermessoEnte::deliveredStatuses(), true);
@endphp
<div data-bs-boundary="window" class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu shadow">
    {{-- DETTAGLIO: Per tutti --}}
    <li>
      <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewModal"
        onclick="Livewire.dispatch('view-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-eye me-2"></i> Dettaglio
      </button>
    </li>

    {{-- MODIFICA: Per tutti --}}
    <li>
      <button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#editModal"
        onclick="Livewire.dispatch('edit-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-pencil me-2"></i> Modifica
      </button>
    </li>

    {{-- DUPLICA: Solo Admin --}}
    @role('admin')
    <li>
      <button type="button" class="dropdown-item"
        onclick="if (confirm('Duplicare il permesso #{{ $row->id }}? Verrà creata una copia in stato Da Lavorare.')) Livewire.dispatch('duplicate-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-files me-2"></i> Duplica
      </button>
    </li>
    @endrole

    <li><hr class="dropdown-divider"></li>

    {{-- PRENDI IN CARICO: Solo ruolo 'permessi ente' e se stato è 'Da Lavorare' --}}
    @role('permessi ente')
    @if ($row->status == 'Da Lavorare')
      <li>
        <button type="button" class="dropdown-item text-primary" onclick="Livewire.dispatch('take-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-hand-index-thumb me-2"></i> Prendi in carico
        </button>
      </li>
    @endif
    @endrole

    {{-- CONSEGNA LAVORAZIONE: Se stato è 'In Lavorazione' --}}
    @if ($row->status == 'In Lavorazione')
      <li>
        <button type="button" class="dropdown-item text-warning"
          onclick="if (confirm('Consegnare il permesso #{{ $row->id }}?')) Livewire.dispatch('consegna-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-send me-2"></i> Consegna Lavorazione
        </button>
      </li>
    @endif

    {{-- FINE LAVORI: Solo Admin e se stato è 'Consegnato' (anche se salvato come 'Consegnata') --}}
    @role('admin')
    @if ($isDelivered)
      <li>
        <button type="button" class="dropdown-item text-success"
          onclick="if (confirm('Segnare il permesso #{{ $row->id }} come Fine Lavori?')) Livewire.dispatch('end-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-check-circle me-2"></i> Fine Lavori
        </button>
      </li>
    @endif
    @endrole

    {{-- ELIMINA: Solo Admin --}}
    @role('admin')
    <li><hr class="dropdown-divider"></li>
    <li>
      <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $row->id }}">
        <i class="bi bi-trash me-2"></i> Elimina
      </button>
    </li>
    @endrole
  </ul>

  {{-- Modal Elimina (ID unico per riga) --}}
  @role('admin')
  <div class="modal fade" id="deleteModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5">Sicuro di voler eliminare il permesso #{{ $row->id }}?</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
          <form method="POST" action="{{ route('deletePermesso', ['permesso' => $row]) }}">
            @method('DELETE')
            @csrf
            <button type="submit" class="btn btn-danger">Si, elimina questa lavorazione</button>
          </form>
        </div>
      </div>
    </div>
  </div>
  @endrole
</div>
