<x-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Lavorazioni</h1> <div><a type="button" class="btn btn-success mx-3" href="{{route('addWork')}}"> <i class="bi bi-plus"></i></i> </a></div>
            </div>
            <div class="col-12">
                <livewire:WorksTable></livewire:WorksTable>
            </div>
        </div>
    </div>
</x-layout>

<style>
    
</style>