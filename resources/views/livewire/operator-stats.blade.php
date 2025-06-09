<div>
    {{-- The best athlete wants his opponent at his best. --}}
    <div class="row g-2 mb-4">
        <div class="col-auto">
            <label>Data Inizio</label>
            <input type="date" wire:model.lazy="startDate" class="form-control">
        </div>
        <div class="col-auto">
            <label>Data Fine</label>
            <input type="date" wire:model.lazy="endDate" class="form-control">
        </div>
    </div>
    <table class="table table-hover">
  <thead>
    <tr>
      <th scope="col">Operatore</th>
      <th scope="col">N. Lavorazioni Assegnate</th>
      <th scope="col">N. Lavorazione in carico</th>
      <th scope="col">Tempo medio di lavorazione</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($operators as $op)
        @php
            $avgTime = $op->works->where('status', "Consegnato")->map(fn($w) => \Carbon\Carbon::parse($w->acception_date)->diffInHours($w->delivery_date))
                        ->avg();
        @endphp
        <tr>
        <td>{{$op->name}}</th>
        <td>{{$op->works->count()}}</td>
        <td>{{$op->works->where('status', 'In Lavorazione')->count()}}</td>
        <td>{{round($avgTime, 1)}}</td>
        </tr>
    @endforeach
    
  </tbody>
</table>
</div>
