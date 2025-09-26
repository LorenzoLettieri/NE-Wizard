<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Lavorazioni</h1>
            </div>
            <div class="col-12">
                @if (session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <livewire:operator-table></livewire:operator-table>
            </div>
        </div>
    </div>
    <!-- edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:operator-edit-work></livewire:operator-edit-work>
  </div>
</div>
</x-layout>