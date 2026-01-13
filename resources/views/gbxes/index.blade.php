<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella GBX</h1>

                <div>
                    <a type="button" class="btn btn-success mx-2" href="{{ route('addGbx') }}">
                        <i class="bi bi-plus"></i>
                    </a>
                </div>

                {{-- Export button can be added later if needed --}}
            </div>

            <div class="col-12">
                <livewire:notification-center></livewire:notification-center>
                <livewire:GbxTable></livewire:GbxTable>
            </div>
        </div>
    </div>
</x-layout>

<!-- View Modal -->
<div class="modal fade" id="viewGbxModal" tabindex="-1" aria-labelledby="viewGbxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:view-gbx></livewire:view-gbx>
    </div>
</div>

<!-- edit Modal -->
<div class="modal fade" id="editGbxModal" tabindex="-1" aria-labelledby="editGbxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:gbx-edit></livewire:gbx-edit>
    </div>
</div>