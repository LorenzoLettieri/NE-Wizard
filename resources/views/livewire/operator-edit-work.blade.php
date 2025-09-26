<div>
    {{-- Do your work, then step back. --}}
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="viewModalLabel">Modifica Lavorazione</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="container mt-4">
    
{{-- Stop trying to control. --}}
<div class="col-12 col-md-12 my-3">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if (session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <form wire:submit="update" method="POST">
        @csrf
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-md-12 mb-4">
                    <label for="notes" class="form-label">Note:</label>
                    <textarea class="form-control" id="notes" rows="5" wire:model="notes"></textarea>
                </div>
                <div class="col-12 col-md-12 mb-4">
                    <label for="suspension_history" class="form-label">Storico Sospensioni:</label>
                    <textarea class="form-control" id="suspension_history" rows="5" wire:model="suspension_history"></textarea>
                </div>
            </div>
            <button type="submit" class="btn btn-primary ">Aggiorna Lavorazione</button>
             <button type="button" class="btn btn-secondary mx-5" data-bs-dismiss="modal">Close</button>
        </div>
        
    </form>
</div>


</div>
      </div>
</div>

</div>

