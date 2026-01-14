<div data-bs-boundary="window" class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu shadow">
    {{-- DETTAGLIO: Per tutti --}}
    <li>
      <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal"
        onclick="Livewire.dispatch('view-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-eye me-2"></i> Dettaglio
      </a>
    </li>

    {{-- MODIFICA: Per tutti --}}
    <li>
      <a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#editModal"
        onclick="Livewire.dispatch('edit-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-pencil me-2"></i> Modifica
      </a>
    </li>

    {{-- DUPLICA: Solo Admin e Supervisor --}}
    @hasanyrole('admin|supervisor')
    <li>
      <a class="dropdown-item" href="#" onclick="Livewire.dispatch('duplicate-permesso', {id: {{ $row->id }}})">
        <i class="bi bi-files me-2"></i> Duplica
      </a>
    </li>
    @endhasanyrole

    <hr class="dropdown-divider">

    {{-- PRENDI IN CARICO: Solo ruolo 'permessi ente' e se stato è 'Da Lavorare' --}}
    @role('permessi ente')
    @if ($row->status == 'Da Lavorare')
      <li>
        <a class="dropdown-item text-primary" href="#" onclick="Livewire.dispatch('take-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-hand-index-thumb me-2"></i> Prendi in carico
        </a>
      </li>
    @endif
    @endrole

    {{-- CONSEGNA LAVORAZIONE: Se stato è 'In Lavorazione' --}}
    @if ($row->status == 'In Lavorazione')
      <li>
        <a class="dropdown-item text-warning" href="#"
          onclick="Livewire.dispatch('consegna-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-send me-2"></i> Consegna Lavorazione
        </a>
      </li>
    @endif

    {{-- FINE LAVORI: Solo Admin e Supervisor e se stato è 'Consegnato' --}}
    @hasanyrole('admin|supervisor')
    @if ($row->status == 'Consegnato')
      <li>
        <a class="dropdown-item text-success" href="#" onclick="Livewire.dispatch('end-permesso', {id: {{ $row->id }}})">
          <i class="bi bi-check-circle me-2"></i> Fine Lavori
        </a>
      </li>
    @endif
    @endhasanyrole

    {{-- ELIMINA: Solo Admin e Supervisor --}}
    @hasanyrole('admin|supervisor')
    <li>
      <hr class="dropdown-divider">
      <a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $row->id }}">
        <i class="bi bi-trash me-2"></i> Elimina
      </a>
    </li>
    @endhasanyrole
  </ul>

  {{-- Modal Elimina (ID unico per riga) --}}
  @hasanyrole('admin|supervisor')
  <div class="modal fade" id="deleteModal{{ $row->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5">Sicuro di voler eliminare questa lavorazione?</h1>
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
  @endhasanyrole
</div>