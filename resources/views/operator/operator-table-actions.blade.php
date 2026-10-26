<div class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#viewModal"
        onclick="Livewire.dispatch('view-work', {id: {{$row->id}}})">Dettaglio</button></li>
    @if (!$readOnlyMode && $row->status == "Da Lavorare")
        <li><button type="button" class="dropdown-item" wire:click="takeWork({{$row->id}})">Prendi in carico</button></li>
    @endif
    @if (!$readOnlyMode && $row->status == "In Lavorazione")
        <li><button type="button" class="dropdown-item" wire:click="deliveryWork({{$row->id}})"
            wire:confirm="Consegnare la lavorazione #{{ $row->id }}?">Consegna Lavorazione</button></li>
    @endif
    @if (!$readOnlyMode)
    <li><button type="button" class="dropdown-item" onclick="Livewire.dispatch('edit-work', {id: {{$row->id}}})" data-bs-toggle="modal" data-bs-target="#editModal">Modifica Operazione</button></li>
    @endif
    @if (!$readOnlyMode && $row->status == "In Lavorazione")
        <li><button type="button" class="dropdown-item text-danger" wire:click="suspendWork({{$row->id}})">Sospendi Lavorazione</button></li>
    @endif
    @if (!$readOnlyMode && $row->status == "Sospeso")
        <li><button type="button" class="dropdown-item text-success" wire:click="unsuspendWork({{$row->id}})">Riprendi Lavorazione</button></li>
    @endif
  </ul>
</div>
