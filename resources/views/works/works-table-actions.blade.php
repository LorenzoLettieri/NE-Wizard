<div data-bs-boundary="window" class="dropdown">
  <a  class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu" >
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal" onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">Dettaglio</a></li>
    <li><a class="dropdown-item" href="{{route('editWork', ['id'=>$row->id])}}">Modifica</a></li>
    <li><a class="dropdown-item" href="#" onclick="Livewire.dispatch('duplicate-work', {id: {{$row->id}}})">Duplica</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="#" onclick="Livewire.dispatch('end-work', {id: {{$row->id}}})">Fine Lavori</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" href="#">Elimina 2.0</a></li>

  
  </ul>

  <!-- Modal -->
  <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="deleteModalLabel">Sicuro di voler eliminare questa lavorazione?</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
          <form method="POST" action="{{route('deleteWork', ['work'=>$row])}}">
            @method('DELETE')
            @csrf
                  <button type="submit" class="btn btn-danger">Si, elimina questa lavorazione</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
