<div class="{{ $isShow || $isEdit ? 'modal-content' : 'col-12 col-md-10 mx-auto my-4' }}">
    @if($isShow || $isEdit)
        <div class="modal-header">
            <h5 class="modal-title">
                {{ $isShow ? 'Dettaglio' : 'Modifica' }} Permesso Ente #{{ $permessoEnteId ?? '' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
    @endif

    <div class="{{ $isShow || $isEdit ? 'modal-body' : 'card shadow-sm border-0 rounded-3 p-4' }}">
        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if($isShow && $permessoEnteId)
            {{-- VIEW MODE STYLE --}}
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Informazioni Generali</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Network</span> <strong>{{ $network ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Consegna</span> <strong>{{ $consegna ? \Carbon\Carbon::parse($consegna)->format('d/m/Y') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Progetto</span> <strong>{{ $progetto ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Via</span> <strong>{{ $via ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Regione</span> <strong>{{ $this->regioni[$regione_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Comune</span> <strong>{{ $this->comuni[$comune_id] ?? '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Centrale</span> <strong>{{ $this->centrali[$central_id] ?? '-' }}</strong></li>
                    </ul>
                </div>

                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Date e Stati</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Status</span> <span class="badge bg-primary">{{ $status ?? 'Da Lavorare' }}</span></li>
                        <!-- <li class="list-group-item d-flex justify-content-between"><span>Presa in Carico</span> <strong>{{ $acception_date ? \Carbon\Carbon::parse($acception_date)->format('d/m/Y H:i') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Consegna Op.</span> <strong>{{ $delivery_date ? \Carbon\Carbon::parse($delivery_date)->format('d/m/Y H:i') : '-' }}</strong></li>
                        <li class="list-group-item d-flex justify-content-between"><span>Fine Lavori (Data)</span> <strong>{{ $completion_date ? \Carbon\Carbon::parse($completion_date)->format('d/m/Y H:i') : '-' }}</strong></li> -->
                        @role('admin')<li class="list-group-item d-flex justify-content-between"><span>Mese Saldo</span> <strong>{{ $mese_saldo ? \Carbon\Carbon::parse($mese_saldo)->format('m/Y') : '-' }}</strong></li>@endrole
                    </ul>
                </div>

                <div class="col-12 mt-4">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Dettagli Tecnici</h6>
                    <div class="row g-3 px-3">
                        <div class="col-md-3"><span>AP Chiusini:</span> <strong class="ms-2">{{ $ap_chiusini == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Num. Chiusini:</span> <strong class="ms-2">{{ $num_chiusini ?? '-' }}</strong></div>
                        <div class="col-md-3"><span>Scavo 100m:</span> <strong class="ms-2">{{ $scavo_fino_100m == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Quote Agg.:</span> <strong class="ms-2">{{ $quote_aggiuntive ?? '-' }}</strong></div>
                        <div class="col-md-3"><span>Urgente:</span> <strong class="ms-2">{{ $urgente == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Ordinaria:</span> <strong class="ms-2">{{ $ordinaria == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Fine Lavori (Check):</span> <strong class="ms-2">{{ $fine_lavori == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Data FL:</span> <strong class="ms-2">{{ $data_fl ? \Carbon\Carbon::parse($data_fl)->format('d/m/Y') : '-' }}</strong></div>
                        <div class="col-md-3"><span>RA:</span> <strong class="ms-2">{{ $ra == '1' ? 'SI' : 'NO' }}</strong></div>
                        <div class="col-md-3"><span>Data RA:</span> <strong class="ms-2">{{ $data_ra ? \Carbon\Carbon::parse($data_ra)->format('d/m/Y') : '-' }}</strong></div>
                        <div class="col-md-3"><span>Evaso dal DL:</span> <strong class="ms-2">{{ $evaso_dal_dl ? \Carbon\Carbon::parse($evaso_dal_dl)->format('d/m/Y') : '-' }}</strong></div>
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Allegati</h6>
                    <div class="mt-2">
                        @if(count($existingMedia) > 0)
                            <div class="row g-2">
                                @foreach($existingMedia as $m)
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center p-2 border rounded shadow-sm">
                                            <i class="bi bi-file-earmark-fill text-secondary fs-4 me-2"></i>
                                            <div class="flex-grow-1 text-truncate small" title="{{ $m['file_name'] }}">
                                                {{ $m['file_name'] }}
                                            </div>
                                            <a href="{{ Storage::url($m['file_path']) }}" target="_blank"
                                                class="btn btn-sm btn-link text-primary p-0">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted small italic">Nessun allegato disponibile.</p>
                        @endif
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <h6 class="text-muted text-uppercase small fw-bold">Descrizione</h6>
                    <div class="p-3 rounded shadow-sm border">{{ $descrizione ?? 'Nessuna descrizione.' }}</div>
                </div>

                @role('admin')
                <div class="col-md-6 mt-4">
                    <h6 class="text-muted text-uppercase small fw-bold">Operatori Assegnati</h6>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @foreach($this->operators as $operator)
                            @if(in_array($operator->id, (array)$operator_id))
                                <span class="badge bg-info text-dark p-2"><i class="bi bi-person me-1"></i>{{ $operator->name }}</span>
                            @endif
                        @endforeach
                        @if(empty($operator_id))
                            <span class="text-muted italic small">Nessun operatore assegnato.</span>
                        @endif
                    </div>
                </div>

                <div class="col-12 mt-4">
                    <h6 class="text-muted text-uppercase small fw-bold border-bottom pb-2">Dati Economici (Admin)</h6>
                    <div class="row text-center mt-3">
                        <div class="col-md-3 border-end">
                            <div class="small text-muted">Al DL</div>
                            <div class="fw-bold fs-5">{{ number_format((float)$al_dl, 2, ',', '.') }} €</div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="small text-muted">A NE</div>
                            <div class="fw-bold fs-5">{{ number_format((float)$a_ne, 2, ',', '.') }} €</div>
                        </div>
                        <div class="col-md-3 border-end">
                            <div class="small text-muted">Delta</div>
                            <div class="fw-bold fs-5 text-{{ (float)$delta >= 0 ? 'success' : 'danger' }}">{{ number_format((float)$delta, 2, ',', '.') }} €</div>
                        </div>
                        <div class="col-md-3">
                            <div class="small text-muted">VDC (Totale)</div>
                            <div class="fw-bold fs-5">{{ number_format((float)$vdc1 + (float)$vdc2 + (float)$vdc3 + (float)$vdc4, 2, ',', '.') }} €</div>
                        </div>
                    </div>
                </div>
                @endrole
            </div>
        @else
            {{-- FORM MODE STYLE (CREATE/EDIT) --}}
            <form wire:submit.prevent="save">
                <div class="container-fluid">
                    <h4 class="mb-4 border-bottom pb-2">Informazioni Generali</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Network</label>
                            <input type="number" class="form-control shadow-sm" wire:model="network">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Consegna</label>
                            <input type="date" class="form-control shadow-sm" wire:model="consegna">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Progetto</label>
                            <input type="text" class="form-control shadow-sm" wire:model="progetto">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Via</label>
                            <input type="text" class="form-control shadow-sm" wire:model="via">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Regione</label>
                            <select class="form-select tom-select shadow-sm" wire:model="regione_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->regioni as $id => $nome)
                                    <option value="{{ $id }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Comune</label>
                            <select class="form-select tom-select shadow-sm" wire:model="comune_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->comuni as $id => $nome)
                                    <option value="{{ $id }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Centrale</label>
                            <select class="form-select tom-select shadow-sm" wire:model="central_id">
                                <option value="">-- Seleziona --</option>
                                @foreach ($this->centrali as $id => $nome)
                                    <option value="{{ $id }}">{{ $nome }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label">Descrizione</label>
                            <textarea class="form-control shadow-sm" rows="3" wire:model="descrizione" placeholder="Inserisci una descrizione..."></textarea>
                        </div>
                    </div>

                    <h4 class="mt-5 mb-4 border-bottom pb-2">Stati e Date</h4>
                    <div class="row g-3 mb-4">
                        @foreach ([
                            'ap_chiusini' => 'AP Chiusini',
                            'scavo_fino_100m' => 'Scavo fino a 100 metri',
                            'urgente' => 'Urgente',
                            'ordinaria' => 'Ordinaria',
                            'fine_lavori' => 'Fine Lavori',
                            'ra' => 'RA',
                        ] as $field => $label)
                            <div class="col-md-4">
                                <label class="form-label">{{ $label }}</label>
                                <select class="form-select shadow-sm" wire:model="{{ $field }}">
                                    <option value="">-- Seleziona --</option>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Numero Chiusini</label>
                            <input type="number" class="form-control shadow-sm" wire:model="num_chiusini">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quote aggiuntive</label>
                            <input type="number" class="form-control shadow-sm" wire:model="quote_aggiuntive">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Data FL</label>
                            <input type="date" class="form-control shadow-sm" wire:model="data_fl">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Data RA</label>
                            <input type="date" class="form-control shadow-sm" wire:model="data_ra">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Evaso dal DL</label>
                            <input type="date" class="form-control shadow-sm" wire:model="evaso_dal_dl">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <label class="form-label fw-bold">Assegna Operatori:</label>
                            <select wire:model="operator_id" class="form-select tom-select-multiple shadow-sm" multiple>
                                @foreach ($this->operators as $operator)
                                    <option value="{{$operator->id}}">{{$operator->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @role('admin')
                    <h4 class="mt-5 mb-4 border-bottom pb-2">Contabilità (Admin)</h4>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Al DL (€)</label>
                            <input type="number" step="0.01" class="form-control shadow-sm" wire:model="al_dl">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">A NE (€)</label>
                            <input type="number" step="0.01" class="form-control shadow-sm" wire:model="a_ne">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Delta (€)</label>
                            <input type="number" step="0.01" class="form-control shadow-sm" wire:model="delta">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mese Saldo</label>
                            <input type="month" class="form-control shadow-sm" wire:model="mese_saldo">
                        </div>
                    </div>

                    <div class="row g-2">
                        @for ($i = 1; $i <= 4; $i++)
                            <div class="col-md-3">
                                <label class="form-label">VDC{{ $i }} (€)</label>
                                <input type="number" class="form-control shadow-sm" wire:model="vdc{{ $i }}">
                            </div>
                        @endfor
                    </div>
                    @endrole
                    
                    <h4 class="mt-5 mb-4 border-bottom pb-2">Media</h4>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label">Allegati</label>
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

                        @if($isEdit && count($existingMedia) > 0)
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Allegati Esistenti</label>
                                @if(count($pendingMediaRemovalIds) > 0)
                                    <div class="alert alert-warning mt-2 mb-3">
                                        Gli allegati segnati verranno eliminati solo dopo il salvataggio.
                                    </div>
                                @endif
                                <ul class="list-group">
                                    @foreach($existingMedia as $media)
                                        <li class="list-group-item d-flex justify-content-between align-items-center {{ in_array($media['id'], $pendingMediaRemovalIds, true) ? 'list-group-item-warning' : '' }}">
                                            <span><i class="bi bi-file-earmark text-secondary me-2"></i>{{ $media['file_name'] }}</span>
                                            <div class="d-flex gap-2">
                                                <a href="{{ Storage::url($media['file_path']) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-download"></i> Scarica
                                                </a>
                                                <button type="button" class="btn btn-sm {{ in_array($media['id'], $pendingMediaRemovalIds, true) ? 'btn-outline-secondary' : 'btn-outline-danger' }}" wire:click="toggleMediaRemoval({{ $media['id'] }})">
                                                    <i class="bi {{ in_array($media['id'], $pendingMediaRemovalIds, true) ? 'bi-arrow-counterclockwise' : 'bi-trash' }}"></i>
                                                    {{ in_array($media['id'], $pendingMediaRemovalIds, true) ? 'Annulla' : 'Segna Per Eliminazione' }}
                                                </button>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 text-end">
                        @if(!$isShow)
                            @if($isEdit)
                                <button type="button" class="btn btn-secondary btn-lg px-4 me-2" data-bs-dismiss="modal">Chiudi</button>
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                                    <i class="bi bi-check2-circle me-1"></i> Aggiorna Permesso
                                </button>
                            @else
                                <button type="submit" class="btn btn-primary btn-lg px-5 shadow">
                                    <i class="bi bi-check2-circle me-1"></i> Crea Permesso
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </form>
        @endif
    </div>

    @if($isShow)
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
        </div>
    @endif
</div>

</div>
