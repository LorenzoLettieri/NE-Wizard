<nav class="navbar navbar-expand-lg bg-dark border-bottom shadow-sm">
    <div class="container-fluid mx-5">
      <a class="navbar-brand fw-bold" href="{{route('welcome')}}"> <img src="/images/logo.png" width="75px"> NEwizard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse gap-3" id="navbarNav">
        <ul class="navbar-nav ms-auto">
            @auth
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="#">Ciao {{Auth::user()->name}}</a>
                </li>
            @endauth
            @role('admin')
            <li class="nav-item">
                <a class="nav-link active" href="{{route('users-table')}}">Gestisci Utenti</a>
            </li>
            @endrole
            @hasanyrole('admin|supervisor')
            <li class="nav-item">
                <a class="nav-link active" href="{{route('works-table')}}">Tabella Lavorazioni</a>
            </li>
            @endhasanyrole
            @can('get works')
            <li class="nav-item">
                <a class="nav-link active" href="{{route('operator-table')}}">Bacheca Operatore</a>
            </li>
            @endcan
            @auth
                <li class="nav-item">
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="btn btn-outline-light mx-2">Logout</button>
                    </form>
                </li>
            @endauth
            
        </ul>
      </div>
    </div>
  </nav>