<x-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Gestisci Account</h1> <div><a type="button" class="btn btn-success mx-3" href="{{route('addUser')}}"> <i class="bi bi-plus"></i></i> </a></div>
            </div>
            <div class="col-12 col-md-10">
                @if (session('message'))
                    <div class="alert alert-success">
                        {{ session('message') }}
                    </div>
                @endif
                <livewire:user-table></livewire:user-table>
            </div>
        </div>
    </div>
</x-layout>