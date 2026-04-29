<div>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                <div>
                    <h5 class="mb-1">Filtri report</h5>
                    <p class="text-muted mb-0 small">
                        Il report combina lavorazioni, sospensioni e timesheet per ricostruire l'attività reale degli operatori.
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

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Timeline Attività</h5>
                    <p class="text-muted small mb-0">Presenza, lavoro attivo effettivo, pause, permessi, straordinari e timbrature di turno/pausa.</p>
                </div>
                <div class="operator-activity-legend d-flex flex-wrap gap-2 small">
                    <span><i style="background:#0d6efd"></i> Presenza</span>
                    <span><i style="background:#198754"></i> Lavoro attivo</span>
                    <span><i style="background:#6c757d"></i> Pausa</span>
                    <span><i style="background:#dc3545"></i> Permesso/Ferie</span>
                    <span><i style="background:#6610f2"></i> Straordinario</span>
                    <span><i style="background:#0dcaf0"></i> Timbrature</span>
                </div>
            </div>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-12 col-md-3">
                    <label class="form-label">Vista grafico</label>
                    <select wire:model.live="viewMode" class="form-select">
                        @foreach ($timelineModeOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4" @if ($viewMode !== 'day') style="display:none;" @endif>
                    <label class="form-label">Giorno</label>
                    <select wire:model.live="selectedDay" class="form-select">
                        @foreach ($dayOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4" @if ($viewMode !== 'week') style="display:none;" @endif>
                    <label class="form-label">Settimana</label>
                    <select wire:model.live="selectedWeekStart" class="form-select">
                        @foreach ($weekOptions as $option)
                            <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md">
                    <div class="small text-body-secondary">{{ $timelineWindowLabel }}</div>
                </div>
            </div>
            <div class="operator-activity-chart-shell" wire:ignore>
                        <div id="operator-timeline" style="min-height: 420px;"></div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="mb-1">Recap Operatori</h5>
                    <p class="text-muted small mb-0">Apri solo gli operatori che ti servono. Tutti i pannelli partono chiusi.</p>
                </div>
            </div>

            @if (count($rows) > 0)
                <div class="accordion" id="operator-activity-accordion">
                    @foreach ($rows as $row)
                        <div class="accordion-item operator-activity-panel shadow-sm mb-3 rounded-3 overflow-hidden">
                            <h2 class="accordion-header" id="heading-{{ $row['operator_id'] }}">
                                <button class="accordion-button collapsed gap-3" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#collapse-{{ $row['operator_id'] }}" aria-expanded="false"
                                    aria-controls="collapse-{{ $row['operator_id'] }}">
                                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center w-100 gap-3">
                                        <div class="pe-lg-3">
                                            <div class="fw-semibold fs-5">{{ $row['operator_name'] }}</div>
                                            <div class="small text-body-secondary">
                                                {{ $row['assigned_count'] }} assegnate, {{ $row['nroe_total'] }} N.ROE FL
                                                @if ($canViewEconomicReport)
                                                    , {{ number_format($row['earned_amount'], 2, ',', '.') }} €
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-grid d-sm-flex gap-3 text-start text-sm-end">
                                            <div class="operator-activity-kpi">
                                                <div class="operator-activity-kpi-label">Presenza</div>
                                                <div class="operator-activity-kpi-value">{{ $row['presence_label'] }}</div>
                                            </div>
                                            <div class="operator-activity-kpi">
                                                <div class="operator-activity-kpi-label">Lavoro Attivo</div>
                                                <div class="operator-activity-kpi-value text-success">{{ $row['active_work_label'] }}</div>
                                            </div>
                                            <div class="operator-activity-kpi">
                                                <div class="operator-activity-kpi-label">Utilizzo</div>
                                                <div class="operator-activity-kpi-value">{{ number_format($row['utilization_percentage'], 1, ',', '.') }}%</div>
                                            </div>
                                            <div class="operator-activity-kpi">
                                                <div class="operator-activity-kpi-label">Assegnate</div>
                                                <div class="operator-activity-kpi-value">{{ $row['assigned_count'] }}</div>
                                            </div>
                                            @if ($canViewEconomicReport)
                                                <div class="operator-activity-kpi">
                                                    <div class="operator-activity-kpi-label">Importo</div>
                                                    <div class="operator-activity-kpi-value">{{ number_format($row['earned_amount'], 0, ',', '.') }} €</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse-{{ $row['operator_id'] }}" class="accordion-collapse collapse"
                                aria-labelledby="heading-{{ $row['operator_id'] }}">
                                <div class="accordion-body">
                                    <div class="row g-3 mb-3">
                                        <div class="col-6 col-lg-3">
                                            <div class="operator-activity-detail-card rounded-3 p-3 h-100">
                                                <div class="operator-activity-kpi-label">Pausa</div>
                                                <div class="fs-5 fw-semibold">{{ $row['break_label'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <div class="operator-activity-detail-card rounded-3 p-3 h-100">
                                                <div class="operator-activity-kpi-label">Sospensione</div>
                                                <div class="fs-5 fw-semibold">{{ $row['suspension_label'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <div class="operator-activity-detail-card rounded-3 p-3 h-100">
                                                <div class="operator-activity-kpi-label">Straordinario</div>
                                                <div class="fs-5 fw-semibold">{{ $row['overtime_label'] }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-lg-3">
                                            <div class="operator-activity-detail-card rounded-3 p-3 h-100">
                                                <div class="operator-activity-kpi-label">Tempo Medio</div>
                                                <div class="fs-5 fw-semibold">{{ $row['average_processing_label'] }}</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="operator-activity-detail-card rounded-3 overflow-hidden">
                                                <div class="px-3 py-2 border-bottom">
                                                    <div class="fw-semibold">Dettaglio giornaliero</div>
                                                </div>
                                                <div class="table-responsive" style="min-height: 0;">
                                                    <table class="table table-sm align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th>Data</th>
                                                                <th class="text-end">Presenza</th>
                                                                <th class="text-end">Lavoro attivo</th>
                                                                <th class="text-end">Pausa</th>
                                                                <th class="text-end">Permessi</th>
                                                                <th class="text-end">Straordinari</th>
                                                                <th class="text-end">Utilizzo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ($row['daily_breakdown'] as $day)
                                                                <tr>
                                                                    <td>{{ $day['date_label'] }}</td>
                                                                    <td class="text-end">{{ $day['presence_label'] }}</td>
                                                                    <td class="text-end fw-semibold text-success">{{ $day['active_work_label'] }}</td>
                                                                    <td class="text-end">{{ $day['break_label'] }}</td>
                                                                    <td class="text-end">{{ $day['leave_label'] }}</td>
                                                                    <td class="text-end">{{ $day['overtime_label'] }}</td>
                                                                    <td class="text-end">{{ number_format($day['utilization_percentage'], 1, ',', '.') }}%</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="7" class="text-center text-muted py-3">Nessun dettaglio disponibile nel periodo selezionato.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="operator-activity-detail-card rounded-3 overflow-hidden h-100">
                                                <div class="px-3 py-2 border-bottom">
                                                    <div class="fw-semibold">Riepilogo settimanale</div>
                                                </div>
                                                <div class="table-responsive" style="min-height: 0;">
                                                    <table class="table table-sm align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th>Settimana</th>
                                                                <th class="text-end">Presenza</th>
                                                                <th class="text-end">Lavoro attivo</th>
                                                                <th class="text-end">Utilizzo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ($row['weekly_summary'] as $week)
                                                                <tr>
                                                                    <td>{{ $week['label'] }}</td>
                                                                    <td class="text-end">{{ $week['presence_label'] }}</td>
                                                                    <td class="text-end fw-semibold text-success">{{ $week['active_work_label'] }}</td>
                                                                    <td class="text-end">{{ number_format($week['utilization_percentage'], 1, ',', '.') }}%</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted py-3">Nessun dato settimanale.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="operator-activity-detail-card rounded-3 overflow-hidden h-100">
                                                <div class="px-3 py-2 border-bottom">
                                                    <div class="fw-semibold">Riepilogo mensile</div>
                                                </div>
                                                <div class="table-responsive" style="min-height: 0;">
                                                    <table class="table table-sm align-middle">
                                                        <thead>
                                                            <tr>
                                                                <th>Mese</th>
                                                                <th class="text-end">Presenza</th>
                                                                <th class="text-end">Lavoro attivo</th>
                                                                <th class="text-end">Utilizzo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @forelse ($row['monthly_summary'] as $month)
                                                                <tr>
                                                                    <td>{{ $month['label'] }}</td>
                                                                    <td class="text-end">{{ $month['presence_label'] }}</td>
                                                                    <td class="text-end fw-semibold text-success">{{ $month['active_work_label'] }}</td>
                                                                    <td class="text-end">{{ number_format($month['utilization_percentage'], 1, ',', '.') }}%</td>
                                                                </tr>
                                                            @empty
                                                                <tr>
                                                                    <td colspan="4" class="text-center text-muted py-3">Nessun dato mensile.</td>
                                                                </tr>
                                                            @endforelse
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="operator-activity-empty text-center text-muted py-5">
                    Nessun operatore trovato per i filtri selezionati.
                </div>
            @endif
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let chart = null;
            let lastPayload = null;

            function getThemeConfig() {
                const root = document.documentElement;
                const styles = getComputedStyle(root);

                return {
                    isDark: root.getAttribute('data-bs-theme') === 'dark',
                    text: styles.getPropertyValue('--bs-body-color').trim() || '#212529',
                    muted: styles.getPropertyValue('--bs-secondary-color').trim() || '#6c757d',
                    border: styles.getPropertyValue('--bs-border-color').trim() || '#dee2e6',
                    card: styles.getPropertyValue('--bs-body-bg').trim() || '#ffffff',
                    tooltipBg: root.getAttribute('data-bs-theme') === 'dark' ? '#212529' : '#ffffff',
                    tooltipText: styles.getPropertyValue('--bs-body-color').trim() || '#212529',
                    grid: root.getAttribute('data-bs-theme') === 'dark'
                        ? 'rgba(255,255,255,0.12)'
                        : 'rgba(0,0,0,0.08)',
                };
            }

            const romeTimeFormatters = {
                day: new Intl.DateTimeFormat('it-IT', {
                    timeZone: 'Europe/Rome',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                }),
                week: new Intl.DateTimeFormat('it-IT', {
                    timeZone: 'Europe/Rome',
                    day: '2-digit',
                    month: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false,
                }),
            };

            function formatRomeTimeLabel(value, mode) {
                return (mode === 'day' ? romeTimeFormatters.day : romeTimeFormatters.week)
                    .format(new Date(value));
            }

            function buildOptions(payload) {
                const theme = getThemeConfig();
                const series = payload.series ?? [];
                const timelineConfig = payload.config ?? {};

                return {
                    series,
                    colors: series.map((item) => item.color),
                    chart: {
                        height: 550,
                        type: 'rangeBar',
                        toolbar: { show: false },
                        selection: { enabled: false },
                        zoom: { enabled: false, allowMouseWheelZoom: false },
                        pan: { enabled: false }
                    },
                    plotOptions: {
                        bar: {
                            horizontal: true,
                            rangeBarGroupRows: true,
                            barHeight: '70%'
                        }
                    },
                    xaxis: {
                        type: 'datetime',
                        min: timelineConfig.min,
                        max: timelineConfig.max,
                        labels: {
                            datetimeUTC: false,
                            formatter: function(value, timestamp) {
                                return formatRomeTimeLabel(timestamp ?? value, timelineConfig.mode);
                            },
                            style: {
                                colors: theme.muted
                            }
                        },
                        axisBorder: {
                            color: theme.border
                        },
                        axisTicks: {
                            color: theme.border
                        }
                    },
                    yaxis: {
                        labels: {
                            style: {
                                colors: theme.text
                            }
                        }
                    },
                    legend: {
                        show: true,
                        position: 'top',
                        markers: {
                            fillColors: series.map((item) => item.color)
                        },
                        labels: {
                            colors: theme.text
                        }
                    },
                    grid: {
                        borderColor: theme.grid
                    },
                    theme: {
                        mode: theme.isDark ? 'dark' : 'light'
                    },
                    tooltip: {
                        followCursor: true,
                        intersect: false,
                        custom: function({ seriesIndex, dataPointIndex, w }) {
                            const point = w.globals.initialSeries[seriesIndex].data[dataPointIndex];
                            const eventLine = point.meta.is_event
                                ? '<span>Orario: ' + point.meta.start_local + '</span>'
                                : '<span>' + point.meta.start_local + ' - ' + point.meta.end_local + '</span><br>' +
                                  '<span>' + point.meta.duration_label + '</span>';

                            return '<div class="p-2" style="background:' + theme.tooltipBg + '; color:' + theme.tooltipText + '; border:1px solid ' + theme.border + '; border-radius:0.5rem;">' +
                                '<strong>' + point.meta.type_label + '</strong><br>' +
                                '<span>' + point.meta.label + '</span><br>' +
                                eventLine +
                                '</div>';
                        }
                    }
                };
            }

            function renderChart(payload, force = false) {
                const container = document.querySelector('#operator-timeline');
                if (!container) {
                    return;
                }

                const serialized = JSON.stringify(payload);
                if (!force && serialized === lastPayload && chart) {
                    return;
                }

                lastPayload = serialized;

                if (chart) {
                    chart.destroy();
                }

                chart = new ApexCharts(container, buildOptions(payload));
                chart.render();
            }

            renderChart(@json(['series' => $timelineData, 'config' => $timelineConfig]));

            window.addEventListener('timeline-data', (e) => {
                renderChart({ series: e.detail.series, config: e.detail.config });
            });

            new MutationObserver(() => {
                if (lastPayload) {
                    renderChart(JSON.parse(lastPayload), true);
                }
            }).observe(document.documentElement, {
                attributes: true,
                attributeFilter: ['data-bs-theme']
            });
        });
    </script>
</div>
