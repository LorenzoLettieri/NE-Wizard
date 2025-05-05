<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid mx-5">
      <a class="navbar-brand" href="#">NEwizard</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
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
            @auth
                <li class="nav-item">
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="nav-link active">Logout</button>
                    </form>
                </li>
            @endauth
            
        </ul>
      </div>
    </div>
  </nav>