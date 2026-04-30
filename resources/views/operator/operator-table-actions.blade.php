<div class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewModal"
        onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">Dettaglio</a></li>
    @if (!$readOnlyMode && $row->status == "Da Lavorare")
        <li><a class="dropdown-item" wire:click="takeWork({{$row->id}})">Prendi in carico</a></li>
    @endif
    @if (!$readOnlyMode && $row->status == "In Lavorazione")
        <li><a class="dropdown-item" wire:click="deliveryWork({{$row->id}})">Consegna Lavorazione</a></li>
    @endif
    @if (!$readOnlyMode)
    <li><a class="dropdown-item"onclick="Livewire.dispatch('edit-work', {id: {{$row->id}}})" data-bs-toggle="modal" data-bs-target="#editModal" >Modifica Operazione</a></li>
    @endif
    @if (!$readOnlyMode && $row->status == "In Lavorazione")
        <li><a class="dropdown-item text-danger" wire:click="suspendWork({{$row->id}})">Sospendi Lavorazione</a></li>
    @endif
    @if (!$readOnlyMode && $row->status == "Sospeso")
        <li><a class="dropdown-item text-success" wire:click="unsuspendWork({{$row->id}})">Riprendi Lavorazione</a></li>
    @endif
  </ul>
</div>
