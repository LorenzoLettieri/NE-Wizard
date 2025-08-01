<x-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Monitora Operatori</h1> <div><a type="button" class="btn btn-primary mx-3" href="{{route('accounts-table')}}"> <i class="bi bi-gear-fill"></i> </a></div>
            </div>
            <div class="col-12 col-md-10">
                @if (session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                @endif
                <livewire:operator-stats></livewire:operator-stats>
            </div>
        </div>
    </div>

</x-layout>