{{-- Messaggi dopo un redirect: esito delle azioni e errori di validazione (es. date dell'export) --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="status">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
        <strong>Operazione non riuscita.</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Chiudi"></button>
    </div>
@endif
