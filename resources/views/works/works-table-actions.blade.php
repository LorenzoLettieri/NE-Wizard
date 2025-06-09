<div class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal" onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">Dettaglio</a></li>
    <li><a class="dropdown-item" href="{{route('editWork', ['id'=>$row->id])}}">Modifica</a></li>
    <li><a class="dropdown-item" href="#" onclick="Livewire.dispatch('duplicate-work', {id: {{$row->id}}})">Duplica</a></li>
    <li><hr class="dropdown-divider"></li>
    <li><a class="dropdown-item" href="#" onclick="Livewire.dispatch('end-work', {id: {{$row->id}}})">Fine Lavori</a></li>
    <li><hr class="dropdown-divider"></li>
    <form method="POST" action="{{route('deleteWork', ['work'=>$row])}}">
            @method('DELETE')
            @csrf
        <li><button type="submit" class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteLabel">Elimina</button></li>
    </form>
  </ul>
</div>
