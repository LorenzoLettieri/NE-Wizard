<div class="modal-content">
    <div class="modal-header">
        <h1 class="modal-title fs-5" id="viewModalLabel">Dettaglio Lavorazione</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="container mt-4">
            @if($work)
            <div class="row g-3">

                <div class="col-md-4">
                    <label class="form-label">Compagnia</label>
                    <div class="form-control">{{ $work->company->name ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Stato Lavorazione</label>
                    <div class="form-control">{{ $work->status ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Centrale</label>
                    <div class="form-control">{{ $work->central->central ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Codice NTW</label>
                    <div class="form-control">{{ $work->network ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">AO/CNO</label>
                    <div class="form-control">{{ $work->ao_cno ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ambito NTW</label>
                    <div class="form-control">{{ $work->ntw_scope ?? '' }}</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Descrizione</label>
                    <div class="form-control">{{ $work->description ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">RAME/OTTICO</label>
                    <div class="form-control">{{ $work->type ?? '' }}</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">DAPHNE</label>
                    <div class="form-control">{{ $work?->daphne ? 'Si' : 'No' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">TEMPO DAPHNE</label>
                    <div class="form-control">{{ $work->tempo_daphne ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fase Lavoro</label>
                    <div class="form-control">{{ $work->phase ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Assistente Impresa</label>
                    <div class="form-control">{{ $work->company_assistant ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">N ROE</label>
                    <div class="form-control">{{ $work->nroe ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">WO Number</label>
                    <div class="form-control">{{ $work->wo_number ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">UNICA Number</label>
                    <div class="form-control">{{ $work->unica_number ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Data prevista consegna</label>
                    <div class="form-control">{{ $work->expected_delivery_date?->format('d/m/Y') ?? '-' }}</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Note</label>
                    <div class="form-control" style="min-height: 80px;">{{ $work->notes ?? '' }}</div>
                </div>
{{-- 
                <div class="col-md-4">
                    <label class="form-label">Data In</label>
                    <div class="form-control">{{ $work->date_in_str ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Data Out</label>
                    <div class="form-control">{{ $work->date_out_str ?? '' }}</div>
                </div> --}}

                <div class="col-md-12">
                    <label class="form-label">Storico Sospensioni</label>
                    <div class="form-control" style="min-height: 80px;">{{ $work->suspension_history ?? '' }}</div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Tempo effettivo di lavorazione</label>
                    <div class="form-control">{{ $work->effective_processing_label ?? '-' }}</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Operatori Assegnati</label>
                    <div class="form-control">
                        {{$this->operators ?? ""}}
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Allegati</label>
                    @if($work->media->count() > 0)
                        <ul class="list-group">
                            @foreach($work->media as $media)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-file-earmark text-secondary me-2"></i>{{ $media->file_name }}</span>
                                    <a href="{{ Storage::url($media->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download"></i> Scarica
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="form-control text-muted">Nessun allegato presente.</div>
                    @endif
                </div>

            </div>
            @else
            <div class="text-muted">Nessuna lavorazione selezionata.</div>
            @endif
        </div>

    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
    </div>
</div>
