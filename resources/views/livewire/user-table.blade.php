<div>
    {{-- The Master doesn't talk, he acts. --}}
    <table class="table">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Username</th>
            <th scope="col">Email</th>
            <th scope="col">Ruoli</th>
            <th scope="col">Azioni</th>
          </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr>
                    <th scope="row">{{$user->id}}</th>
                    <td>{{$user->name}}</td>
                    <td>{{$user->email}}</td>
                    <td>@foreach ($user->getRoleNames() as $role) {{$role}} @endforeach</td>
                    <td>
                        <div class="dropdown">
                            <a class="btn btn-primary dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="{{route('editUser', ['id' => $user->id])}}">Modifica Dati utente</a></li>
                                @if($user->hasRole('supervisor'))
                                    <li><a class="dropdown-item" wire:click="removeRole({{$user->id}}, 'supervisor')" href="#">Rimuovi Ruolo Supervisor</a></li>
                                @else
                                    <li><a class="dropdown-item" wire:click="addRole({{$user->id}}, 'supervisor')" href="#">Rendi Supervisor</a></li>
                                @endif

                                @if(!$user->hasRole('admin'))
                                    <li><a class="dropdown-item text-danger" wire:click="deleteUser({{$user->id}})" wire:confirm="Sei sicuro di voler eliminare questo account?" href="#">Elimina Account</a></li>
                                @endif
                            </ul>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
      </table>
</div>
