<x-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-3">
                <a type="button" class="btn btn-primary mx-3" href="{{route("gbxes-table")}}"> <i
                        class="bi bi-arrow-left"></i> Torna Indietro </a>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Aggiungi GBX</h1>
            </div>
            <livewire:gbx-form></livewire:gbx-form>
        </div>
</x-layout>