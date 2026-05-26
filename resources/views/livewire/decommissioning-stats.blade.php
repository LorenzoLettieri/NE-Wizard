<div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">Filtri report Deco</h5>
                    <p class="text-muted mb-0 small">
                        Il report riepiloga i decommissioning per progettista Deco in base alla data di inserimento.
                    </p>
                </div>
                <div class="align-self-lg-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="resetFilters">
                        Reset filtri
                    </button>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label">Data Inizio</label>
                    <input type="date" wire:model.lazy="startDate" class="form-control">
                </div>
                <div class="col-12 col-md-3 col-xl-2">
                    <label class="form-label">Data Fine</label>
                    <input type="date" wire:model.lazy="endDate" class="form-control">
                </div>
                <div class="col-12 col-md-3 col-xl-3">
                    <label class="form-label">Progettista</label>
                    <select wire:model.live="designerId" class="form-select">
                        <option value="">Tutti</option>
                        @foreach ($designerOptions as $designer)
                            <option value="{{ $designer->id }}">{{ $designer->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3 col-xl-3">
                    <label class="form-label">Impresa</label>
                    <select wire:model.live="companyId" class="form-select">
                        <option value="">Tutte</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Fine Lavori</div>
                    <div class="fs-3 fw-bold">{{ $summary['completed_count'] }}</div>
                    <div class="text-muted small">
                        {{ $summary['in_progress_count'] }} in lavorazione, {{ $summary['suspended_count'] }} sospesi
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Prog pagato</div>
                    <div class="fs-3 fw-bold">{{ number_format($summary['paid_prog_total'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Prog da pagare</div>
                    <div class="fs-3 fw-bold">{{ number_format($summary['unpaid_prog_total'], 2, ',', '.') }} &euro;</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Riepilogo progettisti Deco</h5>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Progettista</th>
                            <th class="text-end">In Lavorazione</th>
                            <th class="text-end">Sospesi</th>
                            <th class="text-end">Fine Lavori</th>
                            <th class="text-end">Prog pagato</th>
                            <th class="text-end">Prog da pagare</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['designer_name'] }}</td>
                                <td class="text-end">{{ $row['in_progress_count'] }}</td>
                                <td class="text-end">{{ $row['suspended_count'] }}</td>
                                <td class="text-end">{{ $row['completed_count'] }}</td>
                                <td class="text-end">{{ number_format($row['paid_prog_total'], 2, ',', '.') }} &euro;</td>
                                <td class="text-end">{{ number_format($row['unpaid_prog_total'], 2, ',', '.') }} &euro;</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Nessun progettista Deco trovato.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
