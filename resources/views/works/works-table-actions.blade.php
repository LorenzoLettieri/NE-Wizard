<div data-bs-boundary="window" class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
    <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewModal"
        onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">
        <i class="bi bi-eye me-2"></i> Dettaglio
      </button></li>
    <li><button type="button" class="dropdown-item text-warning" data-bs-toggle="modal" data-bs-target="#editModal"
        onclick="Livewire.dispatch('edit-work', {id: {{$row->id}}})">
        <i class="bi bi-pencil me-2"></i> Modifica
      </button></li>
    <li><button type="button" class="dropdown-item"
        onclick="if (confirm('Duplicare la lavorazione #{{ $row->id }}? Verrà creata una copia in stato Da Lavorare.')) Livewire.dispatch('duplicate-work', {id: {{$row->id}}})">
        <i class="bi bi-files me-2"></i> Duplica
      </button></li>
    @if ($row->status !== 'Fine Lavori')
      <li>
        <hr class="dropdown-divider">
      </li>
      <li><button type="button" class="dropdown-item text-success"
          onclick="if (confirm('Segnare la lavorazione #{{ $row->id }} come Fine Lavori?')) Livewire.dispatch('end-work', {id: {{$row->id}}})">
          <i class="bi bi-check-circle me-2"></i> Fine Lavori
        </button></li>
    @endif
    <li>
      <hr class="dropdown-divider">
    </li>
    <li><button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteWorkModal{{ $row->id }}">
        <i class="bi bi-trash me-2"></i> Elimina
      </button></li>


  </ul>

  <!-- Modal: l'id contiene la riga, altrimenti Bootstrap aprirebbe sempre il modal della prima riga -->
  <div class="modal fade" id="deleteWorkModal{{ $row->id }}" tabindex="-1" aria-labelledby="deleteWorkModalLabel{{ $row->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="deleteWorkModalLabel{{ $row->id }}">Sicuro di voler eliminare la lavorazione #{{ $row->id }}?</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
          <form method="POST" action="{{route('deleteWork', ['work' => $row])}}">
            @method('DELETE')
            @csrf
            <button type="submit" class="btn btn-danger">Si, elimina questa lavorazione</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
