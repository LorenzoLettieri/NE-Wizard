<x-layout>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 mt-3">
                <h1 class="text-center mx-3">Aggiungi Account</h1>
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
                <form method="POST" action="{{route('registerUser')}}">
                    @csrf
                    <div class="mb-3">
                        <label for="name" class="form-label">Username</label>
                        <input type="text" class="form-control" id="name" name="name">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Indirizzo Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Ripeti Password</label>
                        <input type="password" class="form-control" id="password" name="password_confirmation">
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Ruolo</label>
                        <select class="form-select tom-select" id="role" name="role">
                            <option value="">-- Seleziona --</option>
                            @foreach($roles as $role)
                                <option value="{{$role->name}}">{{$role->name}}</option>
                            @endforeach
                        </select>
                    </div>

                    <div id="company-field" class="mb-3" style="display: none;">
                        <label for="company_id" class="form-label">Impresa</label>
                        <select class="form-select tom-select" id="company_id" name="company_id">
                            <option value="">-- Seleziona --</option>
                            @foreach($companies as $company)
                                <option value="{{$company->id}}">{{$company->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Crea Account</button>
                </form>
            </div>
        </div>
</x-layout>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const companyField = document.getElementById('company-field');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'GBX') {
                companyField.style.display = 'block';
            } else {
                companyField.style.display = 'none';
            }
        });
    });
</script>