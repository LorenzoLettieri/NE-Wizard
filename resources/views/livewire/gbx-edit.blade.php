<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Modifica GBX #{{ $gbx->id ?? '' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form wire:submit="update">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Impresa</label>
                    <select wire:model="company_id" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Network</label>
                    <input type="text" class="form-control" wire:model="network">
                </div>
                <div class="col-md-4">
                    <label class="form-label">SDF</label>
                    <input type="text" class="form-control" wire:model="SDF">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Centrale</label>
                    <select wire:model="central_id" class="form-select">
                        <option value="">-- Seleziona --</option>
                        @foreach($centrals as $central)
                            <option value="{{ $central->id }}">{{ $central->central }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Comune</label>
                    <input type="text" class="form-control" wire:model="comune">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" wire:model="client">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Coordinate</label>
                    <input type="text" class="form-control" wire:model="coordinates">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Data</label>
                    <input type="date" class="form-control" wire:model="date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Appuntamento</label>
                    <input type="date" class="form-control" wire:model="appointment_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Sopralluogo</label>
                    <input type="date" class="form-control" wire:model="inspection_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Verbale</label>
                    <input type="date" class="form-control" wire:model="verbal_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Vincolo</label>
                    <input type="date" class="form-control" wire:model="obligation_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Rilascio</label>
                    <input type="date" class="form-control" wire:model="release_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Progetto</label>
                    <input type="date" class="form-control" wire:model="project_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Speedark</label>
                    <input type="date" class="form-control" wire:model="speedark_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Data Aggiornamento Cart.</label>
                    <input type="date" class="form-control" wire:model="cart_update_date">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Adeguato</label>
                    <select wire:model="is_adeguate" class="form-select">
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Permessi</label>
                    <select wire:model="permissions" class="form-select">
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Avanzamento CO</label>
                    <select wire:model="CO_advancement" class="form-select">
                        <option value="1">SI</option>
                        <option value="0">NO</option>
                    </select>
                </div>
                <h4 class="mt-5 mb-4 border-bottom pb-2">Note</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Note Sopralluogo</label>
                        <textarea class="form-control" wire:model="inspection_notes" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Permessi</label>
                        <textarea class="form-control" wire:model="permission_notes" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Progetto</label>
                        <textarea class="form-control" wire:model="project_notes" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Cliente</label>
                        <textarea class="form-control" wire:model="client_notes" rows="3"></textarea>
                    </div>
                </div>
                @role('admin')
                <div class="col-md-2">
                    <label class="form-label">Valore</label>
                    <input type="number" step="0.01" class="form-control" wire:model="value">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pagato Impresa</label>
                    <input type="number" step="0.01" class="form-control" wire:model="company_paid">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pagato Bezzi</label>
                    <input type="number" step="0.01" class="form-control" wire:model="bezzi_paid">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Pagato Progetto</label>
                    <input type="number" step="0.01" class="form-control" wire:model="project_paid">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Direzione Lav.</label>
                    <div class="input-group">
                        <input type="number" step="0.01" class="form-control" wire:model="dl_paid">
                        <span class="input-group-text">€</span>
                    </div>
                </div>
                @endrole

                <h4 class="mt-5 mb-4 border-bottom pb-2">Media</h4>
                <div class="row g-3">
                    <div class="col-md-12">
                        <label class="form-label">Aggiungi Allegati</label>
                        <input type="file" class="form-control" wire:model="files" multiple>
                        <div wire:loading wire:target="files" class="text-primary mt-1">Caricamento in corso...</div>

                        @if($uploadMessage)
                            <div class="alert alert-{{ $uploadMessageType }} mt-3 mb-0">
                                {{ $uploadMessage }}
                            </div>
                        @endif

                        @error('files')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                        @error('files.*')
                            <div class="text-danger small mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    @if(count($files) > 0)
                        <div class="col-md-12">
                            <label class="form-label fw-bold">Allegati Da Salvare</label>
                            <ul class="list-group">
                                @foreach($files as $index => $file)
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span class="text-truncate me-3">{{ $file->getClientOriginalName() }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removePendingFile({{ $index }})">
                                            <i class="bi bi-x-circle"></i> Rimuovi
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if($gbx && $gbx->media->count() > 0)
                        <div class="col-md-12 mt-3">
                            <label class="form-label fw-bold">Allegati Esistenti</label>
                            @if(count($pendingMediaRemovalIds) > 0)
                                <div class="alert alert-warning mt-2 mb-3">
                                    Gli allegati segnati verranno eliminati solo dopo il salvataggio.
                                </div>
                            @endif
                            <ul class="list-group">
                                @foreach($gbx->media as $m)
                                    <li class="list-group-item d-flex justify-content-between align-items-center {{ in_array($m->id, $pendingMediaRemovalIds, true) ? 'list-group-item-warning' : '' }}">
                                        <span><i class="bi bi-file-earmark text-secondary me-2"></i>{{ $m->file_name }}</span>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('media.download', $m) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-download"></i> Scarica
                                            </a>
                                            <button type="button" class="btn btn-sm {{ in_array($m->id, $pendingMediaRemovalIds, true) ? 'btn-outline-secondary' : 'btn-outline-danger' }}" wire:click="toggleMediaRemoval({{ $m->id }})">
                                                <i class="bi {{ in_array($m->id, $pendingMediaRemovalIds, true) ? 'bi-arrow-counterclockwise' : 'bi-trash' }}"></i>
                                                {{ in_array($m->id, $pendingMediaRemovalIds, true) ? 'Annulla' : 'Segna Per Eliminazione' }}
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @else
                        <div class="col-md-12 mt-3">
                            <p class="text-muted small">Nessun allegato caricato.</p>
                        </div>
                    @endif
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    <button type="submit" class="btn btn-primary">Salva Modifiche</button>
                </div>
            </div>
        </form>
    </div>
</div>
