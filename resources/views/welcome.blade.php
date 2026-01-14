<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            @guest
            <div class="col-12 col-md-4 mt-5 p-5 border rounded-4 border-black shadow-lg">
                <h1 class="text-center">Login</h1>
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="/login">
                    @csrf
                    <div class="mb-3">
                      <label for="exampleInputEmail1" class="form-label">Email address</label>
                      <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                      <label for="exampleInputPassword1" class="form-label">Password</label>
                      <input type="password" class="form-control" id="password" name="password">
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                  </form>
            </div>
            @endguest
            @auth
            <header class="hero-header position-relative text-white mb-4">
                <div class="hero-bg"></div>

                <div class="hero-content position-absolute top-50 start-0 translate-middle-y px-5">
                    <div class="bg-dark bg-opacity-50 p-4 rounded shadow-lg">
                        <h1 class="display-4 fw-bold m-0">Ciao, <span class="text-primary">{{ Auth::user()->name }}</span></h1>
                    </div>
                </div>
            </header>
            <!-- <div class="container mt-4">
                <div class="row justify-content-center">
                    <div class="col-12 col-md-8">
                        <div class="border border-black rounded-4 p-4 shadow-lg">
                            <h2 class="fw-bold mb-3">📌 Patch Notes</h2>
                            <ul class="list-unstyled m-0">
                                <li class="mb-2"><i class="bi bi-calendar-date text-primary me-2"></i> Aggiunto l'export in Excel !!! Urrà! 😎</i></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div> -->
            <div class="container">
                <div class="row my-5">
                @hasanyrole('admin|supervisor')
                    <div class="col-12 col-md-4">
                        <a href="{{route('works-table')}}" class="text-decoration-none">
                        <div class="border shadow-lg border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">Lavorazioni</h3>
                        </div>
                        </a>
                    </div>
                @endhasanyrole
                @role('admin')
                    <div class="col-12 col-md-4">
                        <a href="{{route('users-table')}}" class="text-decoration-none">
                        <div class="border shadow-lg border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">Utenti</h3>
                        </div>
                        </a>
                    </div>
                @endrole
                @can('get works')
                    <div class="col-12 col-md-4">
                        <a href="{{route(name: 'operator-table')}}" class="text-decoration-none">
                        <div class="border border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">Dashboard</h3>
                        </div>
                        </a>
                    </div>
                @endcan
                @hasanyrole('admin|supervisor|permessi ente')
                    <div class="col-12 col-md-4">
                        <a href="{{route('permessi-ente.table')}}" class="text-decoration-none">
                        <div class="border border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">Permessi</h3>
                        </div>
                        </a>
                    </div>
                @endhasanyrole
                @can('get gbx')
                    <div class="col-12 col-md-4 my-md-5">
                        <a href="{{route('gbxes-table')}}" class="text-decoration-none">
                        <div class="border border-black rounded-4 p-4 d-flex justify-content-between hover-lighten">
                            <h3 class="display-5">GBX</h3>
                        </div>
                        </a>
                    </div>
                @endcan 
                </div>
            </div>
            @endauth
        </div>
    </div>
</x-layout>