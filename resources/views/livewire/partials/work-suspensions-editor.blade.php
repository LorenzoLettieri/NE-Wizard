<div class="col-12 col-md-12 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label mb-0">Sospensioni strutturate</label>
        <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addSuspension">
            <i class="bi bi-plus-lg"></i> Aggiungi sospensione
        </button>
    </div>

    <div class="form-text mb-3">
        Inserisci date e orari in fuso Italia. La fine puo restare vuota solo per una sospensione aperta.
    </div>

    @if (count($suspensions) === 0)
        <div class="alert alert-light border mb-0">
            Nessuna sospensione strutturata registrata.
        </div>
    @else
        <div class="d-flex flex-column gap-3">
            @foreach ($suspensions as $index => $suspension)
                <div class="border rounded p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label">Inizio sospensione</label>
                            <input
                                type="datetime-local"
                                class="form-control @error('suspensions.' . $index . '.started_at') is-invalid @enderror"
                                wire:model="suspensions.{{ $index }}.started_at"
                            >
                            @error('suspensions.' . $index . '.started_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-5">
                            <label class="form-label">Fine sospensione</label>
                            <input
                                type="datetime-local"
                                class="form-control @error('suspensions.' . $index . '.ended_at') is-invalid @enderror"
                                wire:model="suspensions.{{ $index }}.ended_at"
                            >
                            @error('suspensions.' . $index . '.ended_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12 col-md-2">
                            <button type="button" class="btn btn-outline-danger w-100" wire:click="removeSuspension({{ $index }})">
                                <i class="bi bi-trash"></i> Rimuovi
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
