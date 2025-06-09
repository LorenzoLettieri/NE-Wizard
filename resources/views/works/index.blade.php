<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Lavorazioni</h1> <div><a type="button" class="btn btn-success mx-3" href="{{route('addWork')}}"> <i class="bi bi-plus"></i></i> </a></div>
            
            </div>
            <div class="col-12">
                @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
              @endif
                <livewire:WorksTable></livewire:WorksTable>
            </div>
        </div>
    </div>
</x-layout>
<!-- Delete Modal -->
{{-- <div class="modal fade" id="deleteLabel" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
</div> --}}
<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:view-work></livewire:view-work>
  </div>
</div>
<style>
    
</style>