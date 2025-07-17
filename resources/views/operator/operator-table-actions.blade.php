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
  </ul>
</div>