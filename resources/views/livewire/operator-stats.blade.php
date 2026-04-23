<div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">Filtri report</h5>
                    <p class="text-muted mb-0 small">
                        I conteggi usano la data assegnazione; gli importi usano la Data FL nello stesso periodo.
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
                <div class="col-12 col-md-6 col-xl-3">
                    <label class="form-label">Operatore</label>
                    <select wire:model.live="operatorId" class="form-select">
                        <option value="">Tutti</option>
                        @foreach ($operatorOptions as $operator)
                            <option value="{{ $operator->id }}">{{ $operator->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label">Status</label>
                    <select wire:model.live="status" class="form-select">
                        <option value="">Tutti</option>
                        @foreach ($statusOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label">Impresa</label>
                    <select wire:model.live="companyId" class="form-select">
                        <option value="">Tutte</option>
                        @foreach ($companyOptions as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-xl-3">
                    <label class="form-label">Fase</label>
                    <select wire:model.live="workPhaseId" class="form-select">
                        <option value="">Tutte</option>
                        @foreach ($workPhaseOptions as $phase)
                            <option value="{{ $phase->id }}">{{ $phase->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-xl-2">
                    <label class="form-label">Ambito NTW</label>
                    <select wire:model.live="ntwScope" class="form-select">
                        <option value="">Tutti</option>
                        @foreach ($ntwScopeOptions as $scope)
                            <option value="{{ $scope }}">{{ $scope }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if ($canViewEconomicReport)
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Importo maturato totale</div>
                        <div class="fs-3 fw-bold">
                            {{ number_format($economicSummary['total_earned'], 2, ',', '.') }} €
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Avanzamento su quota</div>
                        <div class="fs-3 fw-bold">{{ number_format($economicSummary['target_percentage'], 1, ',', '.') }}%</div>
                        <div class="text-muted small">
                            Quota: {{ number_format($economicSummary['total_target'], 2, ',', '.') }} €
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small">Lavorazioni valorizzate</div>
                        <div class="fs-3 fw-bold">{{ $economicSummary['earned_works_count'] }}</div>
                        <div class="text-muted small">
                            {{ $economicSummary['missing_amount_count'] }} con Data FL senza importo
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <div>
                    <h5 class="mb-1">Contachilometri operatori</h5>
                    <p class="text-muted small mb-0">
                        Quota mensile fissa: {{ number_format($monthlyTarget, 2, ',', '.') }} €.
                    </p>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($rows as $row)
                    <div class="col-12 col-md-6 col-xl-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3 mb-2">
                                    <div>
                                        <div class="fw-semibold">{{ $row['operator_name'] }}</div>
                                        <div class="text-muted small">
                                            {{ $row['earned_works_count'] }} lavori valorizzati
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <div class="fw-bold">
                                            {{ number_format($row['earned_amount'], 2, ',', '.') }} €
                                        </div>
                                        <div class="text-muted small">
                                            {{ number_format($row['target_percentage'], 1, ',', '.') }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="progress" role="progressbar" aria-label="Avanzamento quota {{ $row['operator_name'] }}" aria-valuenow="{{ $row['target_percentage'] }}" aria-valuemin="0" aria-valuemax="100" style="height: 1rem;">
                                    <div class="progress-bar {{ $row['target_class'] }}" style="width: {{ $row['target_bar_width'] }}%"></div>
                                </div>
                                @if ($row['missing_amount_count'] > 0)
                                    <div class="small text-warning mt-2">
                                        {{ $row['missing_amount_count'] }} lavorazioni con Data FL senza importo.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th scope="col">Operatore</th>
                            <th scope="col" class="text-end">Assegnate</th>
                            <th scope="col" class="text-end">Da lavorare</th>
                            <th scope="col" class="text-end">In carico</th>
                            <th scope="col" class="text-end">Sospese</th>
                            <th scope="col" class="text-end">Consegnate</th>
                            <th scope="col" class="text-end">Fine lavori</th>
                            <th scope="col" class="text-end">KO</th>
                            <th scope="col" class="text-end">Tempo medio</th>
                            <th scope="col" class="text-end">N.ROE Data FL</th>
                            @if ($canViewEconomicReport)
                                <th scope="col" class="text-end">Importo maturato</th>
                                <th scope="col" class="text-end">% quota</th>
                                <th scope="col" class="text-end">Senza importo</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="fw-semibold">{{ $row['operator_name'] }}</td>
                                <td class="text-end">{{ $row['assigned_count'] }}</td>
                                <td class="text-end">{{ $row['to_do_count'] }}</td>
                                <td class="text-end">{{ $row['in_progress_count'] }}</td>
                                <td class="text-end">{{ $row['suspended_count'] }}</td>
                                <td class="text-end">{{ $row['delivered_count'] }}</td>
                                <td class="text-end">{{ $row['completed_count'] }}</td>
                                <td class="text-end">{{ $row['ko_count'] }}</td>
                                <td class="text-end">{{ $row['average_processing_label'] }}</td>
                                <td class="text-end">{{ $row['nroe_total'] }}</td>
                                @if ($canViewEconomicReport)
                                    <td class="text-end fw-semibold">
                                        {{ number_format($row['earned_amount'], 2, ',', '.') }} €
                                    </td>
                                    <td class="text-end">{{ number_format($row['target_percentage'], 1, ',', '.') }}%</td>
                                    <td class="text-end">{{ $row['missing_amount_count'] }}</td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $canViewEconomicReport ? 13 : 10 }}" class="text-center text-muted py-4">
                                    Nessun operatore trovato per i filtri selezionati.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
