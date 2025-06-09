<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Modifica Lavorazione</h1>
                @if (session('message'))
                <div class="alert alert-success">
                    {{ session('message') }}
                </div>
              @endif
        </div>
        <livewire:work-edit :work="$work"></livewire:work-edit>
    </div>
</x-layout>