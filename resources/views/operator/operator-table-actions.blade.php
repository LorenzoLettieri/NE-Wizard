<div class="dropdown">
  <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
   <i class="bi bi-pencil"></i>
  </a>

  <ul class="dropdown-menu">
    @if ($row->status !== "In Lavorazione")
        <li><a class="dropdown-item" wire:click="takeWork({{$row->id}})">Prendi in carico</a></li>
    @endif
    @if ($row->status == "In Lavorazione")
        <li><a class="dropdown-item" wire:click="deliveryWork({{$row->id}})">Consegna Lavorazione</a></li>
    @endif
    <li><a class="dropdown-item"onclick="Livewire.dispatch('edit-work', {id: {{$row->id}}})" data-bs-toggle="modal" data-bs-target="#editModal" >Modifica Operazione</a></li>
    @if ($row->status !== "Sospeso")
        <li><a class="dropdown-item text-danger" wire:click="suspendWork({{$row->id}})">Sospendi Lavorazione</a></li>
    @endif
    @if ($row->status == "Sospeso")
        <li><a class="dropdown-item text-success" wire:click="unsuspendWork({{$row->id}})">Riprendi Lavorazione</a></li>
    @endif
  </ul>
</div>