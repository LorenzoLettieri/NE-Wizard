<x-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-3">
                <a type="button" class="btn btn-primary mx-3" href="{{ route('decommissionings.table') }}">
                    <i class="bi bi-arrow-left"></i> Torna Indietro
                </a>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Import Excel Decommissioning</h1>
                <p class="text-center text-muted">Modifica massiva dei decommissioning esistenti, identificati dal CLLI</p>
            </div>
            <livewire:decommissioning-import />
        </div>
    </div>
</x-layout>
