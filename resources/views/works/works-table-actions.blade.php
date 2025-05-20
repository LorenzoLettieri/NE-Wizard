<div class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal" onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">Dettaglio</a></li>

    <li><a class="dropdown-item" href="{{route('editWork', ['id'=>$row->id])}}">Modifica</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteLabel">Elimina</a></li>
  </ul>
</div>
<!-- Delete Modal -->
<div class="modal fade" id="deleteLabel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Elimina Lavorazione</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Sicuro di voler eliminare questa lavorazione?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        <form method="POST" action="{{route('deleteWork', ['work'=>$row])}}">
            @method('DELETE')
            @csrf
            <button type="submit" class="btn btn-danger">Elimina</button>
        </form>
      </div>
    </div>
  </div>
</div>
<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:view-work></livewire:view-work>
  </div>
</div>