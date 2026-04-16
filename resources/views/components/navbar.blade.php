<nav class="navbar navbar-expand-lg border-bottom shadow-sm">
    <div class="container-fluid mx-5">
        <a class="navbar-brand fw-bold" href="{{route('welcome')}}"> <img src="/images/logo.png" width="75px">
            NEwizard</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse gap-3" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                @auth
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{ route('profile') }}"><i
                                class="bi bi-person-circle me-1"></i>Ciao {{Auth::user()->name}}</a>
                    </li>
                @endauth
                @hasanyrole('admin|supervisor')
                <li class="nav-item dropdown">
                    <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Gestione
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        <li><a class="dropdown-item" href="{{route('users-table')}}">Gestisci Utenti</a></li>
                        @hasanyrole('admin')
                        <li><a class="dropdown-item" href="{{route('admin.timesheets')}}">Gestione Presenze</a></li>
                        <li><a class="dropdown-item" href="{{route('admin.base-tables')}}">Tabelle di Base</a></li>
                        @endhasanyrole
                    </ul>
                </li>
                @endrole
                @hasanyrole('admin|supervisor|permessi ente|GBX|Deco')
                <li class="nav-item dropdown">
                    <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Tabelle
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        @hasanyrole('admin|supervisor')
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('works-table')}}">Tabella Lavorazioni</a>
                        </li>
                        @endhasanyrole
                        @hasanyrole('admin|permessi ente')
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('permessi-ente.table')}}">Tabella Permessi</a>
                        </li>
                        @endhasanyrole
                        @hasanyrole('admin|GBX')
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('gbxes-table')}}">Tabella GBX</a>
                        </li>
                        @endhasanyrole
                        @hasanyrole('admin|Deco')
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('decommissionings.table')}}">Tabella Decommissioning</a>
                        </li>
                        @endhasanyrole
                    </ul>
                </li>
                @endhasanyrole
                @hasanyrole('operator|freelance operator')
                <li class="nav-item">
                    <a class="nav-link active" href="{{route('operator-table')}}">Bacheca Operatore</a>
                </li>
                @endhasanyrole
                @hasanyrole('operator|supervisor')
                <li class="nav-item">
                    <a class="nav-link active" href="{{route('operator-timesheet')}}">Timbra Presenza</a>
                </li>
                @endhasanyrole
                @auth
                    <li class="nav-item">
                        <form action="/logout" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-outline-light mx-2">Logout</button>
                        </form>
                    </li>
                @endauth
                <div class="form-check form-switch ms-2 align-self-center">
                    <input class="form-check-input" type="checkbox" id="themeSwitch" aria-label="Toggle theme">
                    <label class="form-check-label" for="themeSwitch">
                        🌙
                    </label>
                </div>
            </ul>
        </div>
    </div>
</nav>
