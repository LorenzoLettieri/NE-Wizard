<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Lavorazioni</h1> <div><a type="button" class="btn btn-success mx-3" href="{{route('addWork')}}"> <i class="bi bi-plus"></i></i> </a></div>
            
            </div>
            <div class="col-12">
                <livewire:notification-center></livewire:notification-center>

                <livewire:WorksTable></livewire:WorksTable>
            </div>
        </div>
    </div>
</x-layout>
<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:view-work></livewire:view-work>
  </div>
</div>

<!-- edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:edit-work></livewire:edit-work>
  </div>
</div>
<style>
    
</style>