<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title">Dettaglio GBX #{{ $gbx->id ?? '' }}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        @if($gbx)
            <div class="row g-4">
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Informazioni Generali</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Impresa</span>
                            <strong>{{ $gbx->company->name ?? '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Network</span>
                            <strong>{{ $gbx->network ?? '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>SDF</span>
                            <strong>{{ $gbx->SDF ?? '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Centrale</span>
                            <strong>{{ $gbx->central->central ?? '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Comune</span>
                            <strong>{{ $gbx->comune ?? '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Cliente</span>
                            <strong>{{ $gbx->client ?? '-' }}</strong>
                        </li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted text-uppercase small fw-bold">Date Importanti</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between"><span>Appuntamento</span>
                            <strong>{{ $gbx->appointment_date ? \Carbon\Carbon::parse($gbx->appointment_date)->format('d/m/Y') : '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Sopralluogo</span>
                            <strong>{{ $gbx->inspection_date ? \Carbon\Carbon::parse($gbx->inspection_date)->format('d/m/Y') : '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Verbale</span>
                            <strong>{{ $gbx->verbal_date ? \Carbon\Carbon::parse($gbx->verbal_date)->format('d/m/Y') : '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Vincolo</span>
                            <strong>{{ $gbx->obligation_date ? \Carbon\Carbon::parse($gbx->obligation_date)->format('d/m/Y') : '-' }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between"><span>Rilascio</span>
                            <strong>{{ $gbx->release_date ? \Carbon\Carbon::parse($gbx->release_date)->format('d/m/Y') : '-' }}</strong>
                        </li>
                    </ul>
                </div>
                <h4 class="mt-5 mb-4 border-bottom pb-2">Note</h4>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Note Sopralluogo</label>
                        <textarea readonly class="form-control" rows="3">{{ $gbx->inspection_notes }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Permessi</label>
                        <textarea readonly class="form-control" rows="3">{{ $gbx->permission_notes }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Progetto</label>
                        <textarea readonly class="form-control" rows="3">{{ $gbx->project_notes }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Note Cliente</label>
                        <textarea readonly class="form-control" rows="3">{{ $gbx->client_notes }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="col-12 mt-4 mb-4">
                            <h6 class="text-muted text-uppercase small fw-bold">Documentazione Media</h6>
                            <div class="mt-2">
                                @if($gbx->media->count() > 0)
                                    <div class="row g-2">
                                        @foreach($gbx->media as $m)
                                            <div class="col-md-4">
                                                <div class="d-flex align-items-center p-2 border rounded shadow-sm">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2"></i>
                                                    <div class="flex-grow-1 text-truncate small" title="{{ $m->file_name }}">
                                                        {{ $m->file_name }}
                                                    </div>
                                                    <a href="{{ Storage::url($m->file_path) }}" target="_blank"
                                                        class="btn btn-sm btn-link text-primary p-0">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-muted small italic">Nessun documento disponibile.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                            <div class="d-flex gap-3">
                                <div class="badge {{ $gbx->is_adeguate ? 'bg-success' : 'bg-danger' }} p-2">Adeguato:
                                    {{ $gbx->is_adeguate ? 'SI' : 'NO' }}
                                </div>
                                <div class="badge {{ $gbx->permissions ? 'bg-success' : 'bg-danger' }} p-2">Permessi:
                                    {{ $gbx->permissions ? 'SI' : 'NO' }}
                                </div>
                                <div class="badge {{ $gbx->CO_advancement ? 'bg-success' : 'bg-secondary' }} p-2">
                                    Avanzamento CO:
                                    {{ $gbx->CO_advancement ? 'SI' : 'NO' }}
                                </div>
                            </div>
                        </div>
                        @role('admin')
                        <div class="col-12">
                            <h6 class="text-muted text-uppercase small fw-bold">Contabilità</h6>
                            <div class="row text-center mt-2">
                                <div class="col-md-4 border-end">
                                    <div class="small text-muted">Valore</div>
                                    <div class="fw-bold fs-5">{{ number_format($gbx->value, 2, ',', '.') }} €</div>
                                </div>
                                <div class="col-md-2 border-end">
                                    <div class="small text-muted">Pagato Impresa</div>
                                    <div class="fw-bold">{{ number_format($gbx->company_paid, 2, ',', '.') }} €</div>
                                </div>
                                <div class="col-md-2 border-end">
                                    <div class="small text-muted">Pagato Bezzi</div>
                                    <div class="fw-bold">{{ number_format($gbx->bezzi_paid, 2, ',', '.') }} €</div>
                                </div>
                                <div class="col-md-2 border-end">
                                    <div class="small text-muted">Pagato Progetto</div>
                                    <div class="fw-bold">{{ number_format($gbx->project_paid, 2, ',', '.') }} €</div>
                                </div>
                                <div class="col-md-2">
                                    <div class="small text-muted">Pagato DL</div>
                                    <div class="fw-bold">{{ number_format($gbx->dl_paid, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                        </div>
                        @endrole
                        
                    @else
                    <div class="text-center p-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>