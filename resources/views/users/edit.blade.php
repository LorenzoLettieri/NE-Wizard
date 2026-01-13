<x-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Modifica Account</h1>
            </div>
            <div class="col-12 col-md-4 my-3">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{route('updateUser', ["id" => $user->id])}}">
                    @csrf
                    @method("PUT")
                    <div class="mb-3">
                        <label for="name" class="form-label">Username</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{$user->name}}">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Indirizzo Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="{{$user->email}}">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="company_id" class="form-label">Impresa</label>
                        <select class="form-select tom-select" id="company_id" name="company_id">
                            <option value="">-- Seleziona --</option>
                            @foreach($companies as $company)
                                <option value="{{$company->id}}" {{ $user->company_id == $company->id ? 'selected' : '' }}>
                                    {{$company->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">Ruolo</label>
                        <select class="form-select tom-select" id="role" name="role">
                            <option value="">-- Seleziona --</option>
                            @foreach($roles as $role)
                                <option value="{{$role->name}}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                    {{$role->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Modifica Account</button>
                </form>
            </div>
        </div>
</x-layout>