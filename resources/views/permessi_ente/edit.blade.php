<x-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12 mt-3">
                <a type="button" class="btn btn-primary mx-3" href="{{ route('permessi-ente.table') }}">
                    <i class="bi bi-arrow-left"></i> Torna Indietro
                </a>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Modifica Permesso Ente #{{ $permesso->id }}</h1>
            </div>

            @if (session('success'))
                <div class="col-12 col-xl-10">
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <livewire:permesso-ente-form :permessoEnte="$permesso" :isEdit="true"></livewire:permesso-ente-form>
        </div>
    </div>
</x-layout>
