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
                @auth
                @php
                    $navUser = auth()->user();
                    $canManageUsers = $navUser->isSuperAdmin();
                    $canManageTimesheets = $navUser->can('timesheets.manage');
                    $canBaseTables = $navUser->canAccessBaseTables();
                    $canOperatorReports = $navUser->canViewOperatorReports();
                    $canDecoReport = $navUser->canViewDecommissioningReport();
                    $canWorksTable = $navUser->canViewAllWorks();
                    $canPermessi = $navUser->can('permessi.access');
                    $canGbx = $navUser->can('gbx.access');
                    $canDeco = $navUser->can('deco.access');
                @endphp
                @if ($canManageUsers || $canManageTimesheets || $canBaseTables)
                <li class="nav-item dropdown">
                    <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Gestione
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                        @if ($canManageUsers)
                        <li><a class="dropdown-item" href="{{route('accounts-table')}}">Gestisci Utenti</a></li>
                        <li><a class="dropdown-item" href="{{route('admin.roles')}}">Ruoli e Permessi</a></li>
                        @endif
                        @if ($canManageTimesheets)
                        <li><a class="dropdown-item" href="{{route('admin.timesheets')}}">Gestione Presenze</a></li>
                        @endif
                        @if ($canBaseTables)
                        <li><a class="dropdown-item" href="{{route('admin.base-tables')}}">Tabelle di Base</a></li>
                        @endif
                    </ul>
                </li>
                @endif
                @if ($canOperatorReports || $canDecoReport)
                <li class="nav-item dropdown">
                    <a class="nav-link active dropdown-toggle" href="#" id="navbarReportsDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Report
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarReportsDropdown">
                        @if ($canOperatorReports)
                        <li><a class="dropdown-item" href="{{route('reports.operators')}}">Report Operatori</a></li>
                        @endif
                        @if ($canDecoReport)
                        <li><a class="dropdown-item" href="{{route('reports.decommissioning')}}">Report Deco</a></li>
                        @endif
                    </ul>
                </li>
                @endif
                @if ($canWorksTable || $canPermessi || $canGbx || $canDeco)
                <li class="nav-item dropdown">
                    <a class="nav-link active dropdown-toggle" href="#" id="navbarTablesDropdown" role="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                        Tabelle
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navbarTablesDropdown">
                        @if ($canWorksTable)
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('works-table')}}">Tabella Lavorazioni</a>
                        </li>
                        @endif
                        @if ($canPermessi)
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('permessi-ente.table')}}">Tabella Permessi</a>
                        </li>
                        @endif
                        @if ($canGbx)
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('gbxes-table')}}">Tabella GBX</a>
                        </li>
                        @endif
                        @if ($canDeco)
                        <li class="nav-item">
                            <a class="dropdown-item" href="{{route('decommissionings.table')}}">Tabella Decommissioning</a>
                        </li>
                        @endif
                    </ul>
                </li>
                @endif
                @can('works.operate')
                <li class="nav-item">
                    <a class="nav-link active" href="{{route('operator-table')}}">Bacheca Operatore</a>
                </li>
                @endcan
                @can('timesheets.clock')
                <li class="nav-item">
                    <a class="nav-link active" href="{{route('operator-timesheet')}}">Timbra Presenza</a>
                </li>
                @endcan
                @endauth
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
