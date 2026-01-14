<div data-bs-boundary="window" class="dropdown">
    <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi bi-pencil"></i>
    </a>

    <ul class="dropdown-menu">
        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewGbxModal"
                onclick="Livewire.dispatch('view-gbx', {id: {{$row->id}}})">
                <i class="bi bi-eye me-2"></i> Dettaglio
            </a></li>
        <li><a class="dropdown-item text-warning" href="#" data-bs-toggle="modal" data-bs-target="#editGbxModal"
                onclick="Livewire.dispatch('edit-gbx', {id: {{$row->id}}})">
                <i class="bi bi-pencil me-2"></i> Modifica
            </a></li>
        <li>
            <hr class="dropdown-divider">
        </li>
        @role('admin')
        <li><a class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteGbxModal{{$row->id}}"
                href="#">
                <i class="bi bi-trash me-2"></i> Elimina
            </a></li>
        @endrole
    </ul>

    <!-- Modal -->
    <div class="modal fade" id="deleteGbxModal{{$row->id}}" tabindex="-1" aria-labelledby="deleteGbxModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteGbxModalLabel">Sicuro di voler eliminare questo GBX?</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <form method="POST" action="{{route('deleteGbx', ['gbx' => $row])}}">
                        @method('DELETE')
                        @csrf
                        <button type="submit" class="btn btn-danger">Si, elimina</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>