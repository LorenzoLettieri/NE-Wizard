<div class="{{ $isShow || $isEdit ? 'modal-content' : 'col-12 col-xl-10 mx-auto my-4' }}">
    @if ($isShow || $isEdit)
        <div class="modal-header">
            <h5 class="modal-title">
                {{ $isShow ? 'Dettaglio' : 'Modifica' }} Decommissioning #{{ $decommissioningId ?? '' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    <div class="{{ $isShow || $isEdit ? 'modal-body' : 'card shadow-sm border-0 rounded-3 p-4' }}">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isShow && $decommissioningId)
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Informazioni Generali</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>CLLI</span><strong>{{ $clli ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Impresa</span><strong>{{ $this->companies[$company_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Centrale</span><strong>{{ $this->centrali[$central_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Comune</span><strong>{{ $this->comuni[$comune_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Regione</span><strong>{{ $this->regioni[$regione_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Permesso Ente</span><strong>{{ $permesso_ente ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>FL Permesso</span><strong>{{ $fl_permesso ? \Carbon\Carbon::parse($fl_permesso)->format('d/m/Y') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Progettista</span><strong>{{ $this->progettisti[$progettista_id] ?? '-' }}</strong></li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Workflow</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Stato</span><span class="badge bg-primary">{{ $status ?? 'Da Lavorare' }}</span></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Data IL</span><strong>{{ $data_il ? \Carbon\Carbon::parse($data_il)->format('d/m/Y') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Data FL</span><strong>{{ $data_fl ? \Carbon\Carbon::parse($data_fl)->format('d/m/Y') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between align-items-center"><span>Val</span>
                            @if ($val === '1')
                                <span class="badge rounded-pill text-bg-success"><i class="bi bi-check-lg"></i> Vero</span>
                            @else
                                <span class="badge rounded-pill text-bg-danger"><i class="bi bi-x-lg"></i> Falso</span>
                            @endif
                        </li>
                    </ul>
                </div>

                <div class="col-12">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Quantità Operative</h6>
                    <div class="row g-3 mt-2">
                        @foreach ($this->quantityLabels as $index => $label)
                            <div class="col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="small text-muted mb-2">{{ $label }}</div>
                                    <div class="fw-bold fs-5">{{ ${'qty_' . $index} ?? 0 }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Note Permesso</h6>
                    <div class="p-3 border rounded">{{ $note_permesso ?: 'Nessuna nota disponibile.' }}</div>
                </div>

                <div class="col-12">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Allegati</h6>
                    @if (count($existingMedia) > 0)
                        <div class="row g-2 mt-2">
                            @foreach ($existingMedia as $media)
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center p-2 border rounded shadow-sm">
                                        <i class="bi bi-file-earmark-fill text-secondary fs-4 me-2"></i>
                                        <div class="flex-grow-1 text-truncate small" title="{{ $media['file_name'] }}">
                                            {{ $media['file_name'] }}
                                        </div>
                                        <a href="{{ route('media.download', $media['id']) }}" target="_blank" class="btn btn-sm btn-link text-primary p-0">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted small mt-2 mb-0">Nessun allegato disponibile.</p>
                    @endif
                </div>

                @role('admin')
                    <div class="col-12">
                        <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Contabilità Admin</h6>
                        <div class="row g-3 mt-2">
                            @for ($i = 1; $i <= 6; $i++)
                                <div class="col-md-2">
                                    <div class="border rounded p-2 text-center h-100">
                                        <div class="small text-muted">Prog {{ $i }}.0</div>
                                        <div class="fw-bold">{{ number_format((float) ${'prog_amount_' . $i}, 2, ',', '.') }} €</div>
                                    </div>
                                </div>
                            @endfor
                            @for ($i = 1; $i <= 6; $i++)
                                <div class="col-md-2">
                                    <div class="border rounded p-2 text-center h-100">
                                        <div class="small text-muted">NE {{ $i }}.0</div>
                                        <div class="fw-bold">{{ number_format((float) ${'ne_amount_' . $i}, 2, ',', '.') }} €</div>
                                    </div>
                                </div>
                            @endfor
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <div class="small text-muted">Tot Prog</div>
                                    <div class="fw-bold fs-5">{{ number_format((float) $tot_prog, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <div class="small text-muted">Tot NE</div>
                                    <div class="fw-bold fs-5">{{ number_format((float) $tot_ne, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <div class="small text-muted">Agio</div>
                                    <div class="fw-bold fs-5">{{ number_format((float) $agio, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="border rounded p-3 text-center">
                                    <div class="small text-muted">Pagamenti</div>
                                    <div class="fw-bold">Prog {{ $pagata_prog === '1' ? 'SI' : 'NO' }} / NE {{ $pagata_ne === '1' ? 'SI' : 'NO' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endrole
            </div>
        @else
            <form wire:submit.prevent="save">
                <div class="container-fluid">
                    <h4 class="mb-4 border-bottom pb-2">Informazioni Generali</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">CLLI</label>
                            <input type="text" class="form-control shadow-sm" wire:model="clli">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Impresa</label>
                            <select class="form-select tom-select shadow-sm" wire:model="company_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->companies as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Centrale</label>
                            <select class="form-select tom-select shadow-sm" wire:model="central_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->centrali as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Comune</label>
                            <select class="form-select tom-select shadow-sm" wire:model="comune_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->comuni as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Regione</label>
                            <select class="form-select tom-select shadow-sm" wire:model="regione_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->regioni as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Permesso Ente</label>
                            <input type="text" class="form-control shadow-sm" wire:model="permesso_ente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">FL Permesso</label>
                            <input type="date" class="form-control shadow-sm" wire:model="fl_permesso">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Progettista</label>
                            <select class="form-select tom-select shadow-sm" wire:model="progettista_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->progettisti as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Stato</label>
                            <select class="form-select shadow-sm" wire:model="status">
                                <option value="Da Lavorare">Da Lavorare</option>
                                <option value="In Lavorazione">In Lavorazione</option>
                                <option value="Sospeso">Sospeso</option>
                                <option value="Fine Lavori">Fine Lavori</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data IL</label>
                            <input type="date" class="form-control shadow-sm" wire:model="data_il">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data FL</label>
                            <input type="date" class="form-control shadow-sm" wire:model="data_fl">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Val</label>
                            <select class="form-select shadow-sm" wire:model="val">
                                <option value="0">Falso</option>
                                <option value="1">Vero</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Note Permesso</label>
                            <textarea class="form-control shadow-sm" rows="4" wire:model="note_permesso" placeholder="Inserisci note operative o amministrative..."></textarea>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Quantità Operative</h4>
                    <div class="row g-3 mb-4">
                        @foreach ($this->quantityLabels as $index => $label)
                            <div class="col-md-6">
                                <label class="form-label">{{ $label }}</label>
                                <input type="number" min="0" class="form-control shadow-sm" wire:model.live="qty_{{ $index }}">
                            </div>
                        @endforeach
                    </div>

                    @role('admin')
                        <h4 class="mt-5 mb-4 border-bottom pb-2">Contabilità Admin</h4>
                        <div class="row g-3 mb-4">
                            @for ($i = 1; $i <= 6; $i++)
                                <div class="col-md-2">
                                    <label class="form-label">Prog {{ $i }}.0</label>
                                    <input type="number" step="0.01" min="0" class="form-control shadow-sm" wire:model="prog_amount_{{ $i }}">
                                </div>
                            @endfor
                        </div>

                        <div class="row g-3 mb-4">
                            @for ($i = 1; $i <= 6; $i++)
                                <div class="col-md-2">
                                    <label class="form-label">NE {{ $i }}.0</label>
                                    <input type="number" step="0.01" min="0" class="form-control shadow-sm" wire:model="ne_amount_{{ $i }}">
                                </div>
                            @endfor
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Tot Prog</label>
                                <input type="text" class="form-control shadow-sm" value="{{ number_format((float) $tot_prog, 2, ',', '.') }} €" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tot NE</label>
                                <input type="text" class="form-control shadow-sm" value="{{ number_format((float) $tot_ne, 2, ',', '.') }} €" readonly>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Agio</label>
                                <input type="text" class="form-control shadow-sm" value="{{ number_format((float) $agio, 2, ',', '.') }} €" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Pagata Prog</label>
                                <select class="form-select shadow-sm" wire:model="pagata_prog">
                                    <option value="0">NO</option>
                                    <option value="1">SI</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pagata NE</label>
                                <select class="form-select shadow-sm" wire:model="pagata_ne">
                                    <option value="0">NO</option>
                                    <option value="1">SI</option>
                                </select>
                            </div>
                        </div>
                    @endrole

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Media</h4>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Allegati</label>
                            <input type="file" class="form-control" wire:model="files" multiple>
                            <div wire:loading wire:target="files" class="text-primary mt-1">Caricamento in corso...</div>

                            @if ($uploadMessage)
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

                        @if (count($files) > 0)
                            <div class="col-12">
                                <label class="form-label fw-bold">Allegati Da Salvare</label>
                                <ul class="list-group">
                                    @foreach ($files as $index => $file)
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

                        @if ($isEdit && count($existingMedia) > 0)
                            <div class="col-12">
                                <label class="form-label fw-bold">Allegati Esistenti</label>
                                <div class="row g-2">
                                    @foreach ($existingMedia as $media)
                                        @php $pendingRemoval = in_array($media['id'], $pendingMediaRemovalIds, true); @endphp
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-center justify-content-between p-2 border rounded {{ $pendingRemoval ? 'border-danger bg-danger-subtle' : '' }}">
                                                <div class="d-flex align-items-center gap-2 text-truncate">
                                                    <i class="bi bi-file-earmark-fill text-secondary"></i>
                                                    <a class="text-decoration-none text-truncate" href="{{ route('media.download', $media['id']) }}" target="_blank">
                                                        {{ $media['file_name'] }}
                                                    </a>
                                                </div>
                                                <button type="button" class="btn btn-sm {{ $pendingRemoval ? 'btn-outline-secondary' : 'btn-outline-danger' }}" wire:click="toggleMediaRemoval({{ $media['id'] }})">
                                                    {{ $pendingRemoval ? 'Annulla' : 'Rimuovi' }}
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-4 d-flex justify-content-end gap-2">
                    @if ($isEdit)
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
                    @else
                        <a class="btn btn-light" href="{{ route('decommissionings.table') }}">Annulla</a>
                    @endif
                    <button type="submit" class="btn btn-primary">Salva</button>
                </div>
            </form>
        @endif
    </div>

    @if ($isShow || $isEdit)
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        </div>
    @endif
</div>
