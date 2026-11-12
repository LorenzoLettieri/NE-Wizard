@php
    $step = $resultMessage ? 'done' : ($preview ? 'review' : 'upload');
    $steps = ['upload' => 'Carica il file', 'review' => 'Controlla le modifiche', 'done' => 'Aggiornato'];
    $stepIndex = array_search($step, array_keys($steps), true);
    $updates = $preview['updates'] ?? [];
    $rowErrors = $preview['errors'] ?? [];
@endphp

<div class="col-12 col-xl-10 mx-auto my-4 deco-import">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent px-4 py-3">
            <ol class="deco-import-steps list-unstyled d-flex flex-wrap gap-2 gap-md-4 mb-0" aria-label="Avanzamento import">
                @foreach ($steps as $key => $label)
                    @php($index = $loop->index)
                    <li class="deco-import-step {{ $index < $stepIndex || $step === 'done' ? 'is-done' : '' }} {{ $index === $stepIndex ? 'is-current' : '' }}"
                        @if ($index === $stepIndex) aria-current="step" @endif>
                        <span class="deco-import-step-dot">
                            @if ($index < $stepIndex || $step === 'done')
                                <i class="bi bi-check-lg"></i>
                            @else
                                {{ $index + 1 }}
                            @endif
                        </span>
                        {{ $label }}
                    </li>
                @endforeach
            </ol>
        </div>

        {{-- 1. Caricamento --}}
        @if ($step === 'upload')
            <div class="card-body p-4">
                <div class="row g-4">
                    <div class="col-12 col-lg-7">
                        <div x-data="{ dragging: false, uploading: false, progress: 0 }"
                             x-on:livewire-upload-start="uploading = true; progress = 0"
                             x-on:livewire-upload-progress="progress = $event.detail.progress"
                             x-on:livewire-upload-finish="uploading = false"
                             x-on:livewire-upload-error="uploading = false"
                             x-on:livewire-upload-cancel="uploading = false">
                            <label class="deco-import-drop @error('file') is-invalid @enderror" :class="{ 'is-dragging': dragging }"
                                   x-on:dragenter="dragging = true" x-on:dragleave="dragging = false" x-on:drop="dragging = false">
                                <input type="file" wire:model="file" accept=".xlsx,.xls" aria-describedby="import-file-help">

                                @if ($file && ! $errors->has('file'))
                                    <i class="bi bi-file-earmark-spreadsheet deco-import-drop-icon text-success" aria-hidden="true"></i>
                                    <span class="fw-semibold text-break">{{ $file->getClientOriginalName() }}</span>
                                    <span class="small text-body-secondary" id="import-file-help">{{ number_format($file->getSize() / 1024, 0, ',', '.') }} KB · clicca o trascina per sostituirlo</span>
                                @else
                                    <i class="bi bi-cloud-arrow-up deco-import-drop-icon" aria-hidden="true"></i>
                                    <span class="fw-semibold">Trascina qui il file Excel o <span class="text-primary text-decoration-underline">sceglilo dal computer</span></span>
                                    <span class="small text-body-secondary" id="import-file-help">.xlsx o .xls, massimo 10 MB</span>
                                @endif

                                <div class="progress deco-import-progress" role="progressbar" aria-label="Caricamento file" x-show="uploading" x-cloak
                                     :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar" :style="`width: ${progress}%`"></div>
                                </div>
                            </label>

                            @error('file')
                                <div class="text-danger small mt-2"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
                            @enderror

                            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-2 mt-3">
                                <span class="small text-body-secondary">
                                    <i class="bi bi-shield-check"></i> Nulla viene salvato prima della tua conferma.
                                </span>
                                <button type="button" class="btn btn-primary" wire:click="analyze"
                                        wire:loading.attr="disabled" wire:target="file,analyze" x-bind:disabled="uploading">
                                    <span wire:loading.remove wire:target="analyze"><i class="bi bi-search"></i> Analizza il file</span>
                                    <span wire:loading wire:target="analyze"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Analisi in corso…</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-5">
                        <div class="deco-import-guide rounded-3 p-3 h-100">
                            <h2 class="h6 mb-3">Come preparare il file</h2>
                            <dl class="mb-0 small">
                                <dt>Parti dall'export</dt>
                                <dd>Le colonne si riconoscono dalle intestazioni dell'export. Puoi cancellare quelle che non ti servono.</dd>
                                <dt>CLLI</dt>
                                <dd>Identifica il decommissioning da modificare e non viene mai cambiato.</dd>
                                <dt>Celle vuote</dt>
                                <dd>Vengono ignorate: il valore salvato resta com'è.</dd>
                                <dt>Formati</dt>
                                <dd>SI / NO per Pagata e Val, date gg/mm/aaaa. Impresa, Centrale, Comune, Regione e Progettista per nome.</dd>
                                <dt>Importi</dt>
                                <dd class="mb-0">Tot Prog, Tot NE e Agio si ricalcolano da soli; cambiare quantità o impresa ricalcola gli importi dalle tariffe.</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- 2. Anteprima --}}
        @if ($step === 'review')
            <div class="card-body p-4" x-data="{ tab: '{{ $updates || ! $rowErrors ? 'updates' : 'errors' }}', search: '' }">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
                    <div class="d-flex align-items-center gap-2 deco-import-filename">
                        <i class="bi bi-file-earmark-spreadsheet fs-4 text-success" aria-hidden="true"></i>
                        <div class="deco-import-filename">
                            <div class="fw-semibold text-truncate">{{ $file?->getClientOriginalName() }}</div>
                            @unless ($preview['fatal'])
                                <div class="small text-body-secondary">{{ $preview['rowsRead'] }} {{ $preview['rowsRead'] === 1 ? 'riga letta' : 'righe lette' }}</div>
                            @endunless
                        </div>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="cancel">
                        <i class="bi bi-arrow-repeat"></i> Cambia file
                    </button>
                </div>

                @if ($notice)
                    <div class="alert alert-warning d-flex gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <div>{{ $notice }}</div>
                    </div>
                @endif

                @if ($preview['fatal'])
                    <div class="deco-import-empty text-center p-4">
                        <i class="bi bi-file-earmark-x fs-1 text-danger" aria-hidden="true"></i>
                        <p class="fw-semibold mt-2 mb-1">Il file non si può importare</p>
                        <p class="text-body-secondary mb-3">{{ $preview['fatal'] }}</p>
                        <button type="button" class="btn btn-primary" wire:click="cancel">Scegli un altro file</button>
                    </div>
                @else
                    <dl class="deco-import-summary mb-4">
                        <div>
                            <dt>Da aggiornare</dt>
                            <dd class="{{ $updates ? 'text-primary' : '' }}">{{ count($updates) }}</dd>
                        </div>
                        <div>
                            <dt>Già allineati</dt>
                            <dd>{{ $preview['unchanged'] }}</dd>
                        </div>
                        <div>
                            <dt>Scartate</dt>
                            <dd class="{{ $rowErrors ? 'text-danger' : '' }}">{{ count($rowErrors) }}</dd>
                        </div>
                        <div class="deco-import-summary-columns">
                            <dt>Colonne aggiornate</dt>
                            <dd>
                                @foreach ($preview['columns'] as $column)
                                    <span class="badge rounded-pill text-bg-primary fw-normal">{{ $column['heading'] }}</span>
                                @endforeach
                            </dd>
                        </div>
                    </dl>

                    @if ($preview['ignoredColumns'])
                        <details class="deco-import-ignored small mb-4">
                            <summary class="text-body-secondary">
                                {{ count($preview['ignoredColumns']) }} {{ count($preview['ignoredColumns']) === 1 ? 'colonna ignorata' : 'colonne ignorate' }}
                            </summary>
                            <ul class="list-unstyled mt-2 mb-0">
                                @foreach ($preview['ignoredColumns'] as $column)
                                    <li><span class="fw-semibold">{{ $column['heading'] }}</span> <span class="text-body-secondary">— {{ $column['reason'] }}</span></li>
                                @endforeach
                            </ul>
                        </details>
                    @endif

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                        <ul class="nav nav-pills deco-import-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" role="tab" :class="{ active: tab === 'updates' }" :aria-selected="tab === 'updates'" x-on:click="tab = 'updates'">
                                    Modifiche <span class="badge rounded-pill text-bg-secondary ms-1">{{ count($updates) }}</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button type="button" class="nav-link" role="tab" :class="{ active: tab === 'errors' }" :aria-selected="tab === 'errors'" x-on:click="tab = 'errors'">
                                    Righe scartate <span class="badge rounded-pill {{ $rowErrors ? 'text-bg-danger' : 'text-bg-secondary' }} ms-1">{{ count($rowErrors) }}</span>
                                </button>
                            </li>
                        </ul>
                        @if (count($updates) > 5)
                            <div class="deco-import-search" x-show="tab === 'updates'">
                                <i class="bi bi-search" aria-hidden="true"></i>
                                <input type="search" class="form-control form-control-sm" placeholder="Cerca CLLI o comune" aria-label="Cerca CLLI o comune" x-model="search">
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'updates'" role="tabpanel">
                        @if ($updates)
                            <div class="deco-import-table-wrap">
                                <table class="table table-sm align-middle mb-0 deco-import-table deco-import-table-updates">
                                    <thead>
                                        <tr>
                                            <th scope="col">Decommissioning</th>
                                            <th scope="col">Campo</th>
                                            <th scope="col">Attuale</th>
                                            <th scope="col" class="deco-import-arrow"><span class="visually-hidden">diventa</span></th>
                                            <th scope="col">Nuovo</th>
                                        </tr>
                                    </thead>
                                    @foreach ($updates as $update)
                                        @php($rows = max(count($update['changes']) + ($update['hiddenChanges'] ? 1 : 0), 1))
                                        <tbody wire:key="update-{{ $update['id'] }}"
                                               x-show="! search || @js(mb_strtolower($update['clli'].' '.$update['comune'])).includes(search.toLowerCase())">
                                            @foreach ($update['changes'] as $change)
                                                <tr>
                                                    @if ($loop->first)
                                                        <th scope="rowgroup" rowspan="{{ $rows }}" class="deco-import-record">
                                                            <span class="d-block">{{ $update['clli'] }}</span>
                                                            <span class="d-block small fw-normal text-body-secondary">
                                                                {{ $update['comune'] ? $update['comune'].' · ' : '' }}riga {{ $update['row'] }}
                                                            </span>
                                                        </th>
                                                    @endif
                                                    <td class="text-body-secondary deco-import-field">{{ $change['label'] }}</td>
                                                    <td class="deco-import-old">{{ $change['old'] }}</td>
                                                    <td class="deco-import-arrow"><i class="bi bi-arrow-right" aria-hidden="true"></i></td>
                                                    <td class="deco-import-new">
                                                        {{ $change['new'] }}
                                                        @if ($change['derived'])
                                                            <span class="badge rounded-pill deco-import-derived fw-normal ms-1" title="Ricalcolato automaticamente">calcolato</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                            @if ($update['hiddenChanges'])
                                                <tr>
                                                    @if (! $update['changes'])
                                                        <th scope="rowgroup" class="deco-import-record">
                                                            <span class="d-block">{{ $update['clli'] }}</span>
                                                            <span class="d-block small fw-normal text-body-secondary">riga {{ $update['row'] }}</span>
                                                        </th>
                                                    @endif
                                                    <td colspan="4" class="small text-body-secondary fst-italic">
                                                        <i class="bi bi-calculator" aria-hidden="true"></i> Gli importi verranno ricalcolati dalle tariffe dell'impresa.
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    @endforeach
                                </table>
                            </div>
                        @else
                            <div class="deco-import-empty text-center p-4">
                                <i class="bi bi-check2-all fs-1 text-success" aria-hidden="true"></i>
                                <p class="fw-semibold mt-2 mb-1">Nessuna modifica da applicare</p>
                                <p class="text-body-secondary mb-0">
                                    {{ $rowErrors ? 'Le righe valide coincidono già con i dati salvati; controlla le righe scartate.' : 'I valori nel file coincidono già con quelli salvati.' }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div x-show="tab === 'errors'" x-cloak role="tabpanel">
                        @if ($rowErrors)
                            <p class="small text-body-secondary">Queste righe non verranno applicate. Correggile nel file e caricalo di nuovo, oppure conferma solo le altre modifiche.</p>
                            <div class="deco-import-table-wrap">
                                <table class="table table-sm align-middle mb-0 deco-import-table">
                                    <thead>
                                        <tr>
                                            <th scope="col" class="text-end deco-import-rownum">Riga</th>
                                            <th scope="col">CLLI</th>
                                            <th scope="col">Motivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rowErrors as $rowError)
                                            <tr>
                                                <td class="text-end text-body-secondary deco-import-rownum">{{ $rowError['row'] }}</td>
                                                <td class="fw-semibold text-nowrap">{{ $rowError['clli'] ?: '—' }}</td>
                                                <td>
                                                    @foreach ($rowError['messages'] as $reason)
                                                        <div class="text-danger-emphasis"><i class="bi bi-x-circle me-1" aria-hidden="true"></i>{{ $reason }}</div>
                                                    @endforeach
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="deco-import-empty text-center p-4">
                                <i class="bi bi-check2-circle fs-1 text-success" aria-hidden="true"></i>
                                <p class="fw-semibold mt-2 mb-0">Tutte le righe del file sono valide</p>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            @if ($planHash)
                @php($count = count($updates))
                <div class="card-footer deco-import-actions d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 px-4 py-3">
                    <div class="small">
                        <span class="fw-semibold">{{ $count }} {{ $count === 1 ? 'decommissioning verrà aggiornato' : 'decommissioning verranno aggiornati' }}.</span>
                        <span class="text-body-secondary">
                            @if ($rowErrors)
                                {{ count($rowErrors) }} {{ count($rowErrors) === 1 ? 'riga scartata resta invariata' : 'righe scartate restano invariate' }}.
                            @endif
                            L'operazione non si può annullare.
                        </span>
                    </div>
                    <div class="d-flex gap-2 flex-shrink-0">
                        <button type="button" class="btn btn-outline-secondary" wire:click="cancel">Annulla</button>
                        <button type="button" class="btn btn-success" wire:click="apply" wire:loading.attr="disabled" wire:target="apply"
                                wire:confirm="Confermi l'aggiornamento di {{ $count }} decommissioning? L'operazione non si può annullare.">
                            <span wire:loading.remove wire:target="apply"><i class="bi bi-check2-circle"></i> Conferma e aggiorna</span>
                            <span wire:loading wire:target="apply"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Salvataggio…</span>
                        </button>
                    </div>
                </div>
            @endif
        @endif

        {{-- 3. Esito --}}
        @if ($step === 'done')
            <div class="card-body p-5 text-center" role="status">
                <span class="deco-import-success-icon"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                <p class="h5 mt-3 mb-1">{{ $resultMessage }}</p>
                <p class="text-body-secondary mb-4">Le modifiche sono già visibili nella tabella.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                    <a href="{{ route('decommissionings.table') }}" class="btn btn-primary"><i class="bi bi-table"></i> Vai alla tabella</a>
                    <button type="button" class="btn btn-outline-secondary" wire:click="cancel"><i class="bi bi-file-earmark-arrow-up"></i> Importa un altro file</button>
                </div>
            </div>
        @endif
    </div>
</div>
