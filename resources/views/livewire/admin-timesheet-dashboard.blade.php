<div class="container-fluid p-4">
    <h1 class="h3 mb-4">Gestione Presenze</h1>

    @if (session()->has('timesheetSuccess'))
        <div class="alert alert-success py-2">
            {{ session('timesheetSuccess') }}
        </div>
    @endif

    <!-- Detailed View Section -->
    <div class="card shadow-sm mb-5">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0">Dettaglio Giornaliero</h5>

            <div class="d-flex align-items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="detailedSearch" class="form-control form-control-sm"
                    placeholder="Cerca operatore...">

                <div class="d-flex align-items-center rounded border py-1">
                    <button wire:click="previousPeriod" class="btn btn-sm btn-link text-decoration-none p-0 text-dark">
                        <i class="bi bi-chevron-left"></i>
                    </button>
                    <input type="date" wire:model.live="viewDate"
                        class="form-control form-control-sm border-0 shadow-none fw-bold"
                        style="width: auto; background: transparent;">
                    <button wire:click="nextPeriod" class="btn btn-sm btn-link text-decoration-none p-0 text-dark">
                        <i class="bi bi-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table">
                <table class="table table-hover table-striped mb-0 align-middle text-sm">
                    <thead class="">
                        <tr>
                            <th>Operatore</th>
                            <th>Data</th>
                            <th>Entrata</th>
                            <th>Uscita</th>
                            <th>Pausa</th>
                            <th>Totale</th>
                            <th>Permessi</th>
                            <th>Str.</th>
                            <th class="text-end pe-3">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->detailedTimesheets as $ts)
                            <tr>
                                <td class="fw-medium">{{ $ts->user->name }}</td>
                                <td>{{ $ts->date->format('d/m/Y') }}</td>
                                <td>{{ $ts->entry_time ? $ts->entry_time->timezone('Europe/Rome')->format('H:i') : '-' }}
                                </td>
                                <td>{{ $ts->exit_time ? $ts->exit_time->timezone('Europe/Rome')->format('H:i') : '-' }}</td>
                                <td>
                                    @if($ts->break_start && $ts->break_end)
                                        {{ round($ts->break_start->diffInMinutes($ts->break_end)) }}m
                                    @elseif($ts->break_start)
                                        In corso
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold">
                                    @php
                                        $totalMinutes = 0;
                                        if ($ts->entry_time && $ts->exit_time) {
                                            $totalMinutes = $ts->entry_time->diffInMinutes($ts->exit_time);
                                            if ($ts->break_start && $ts->break_end) {
                                                $totalMinutes -= $ts->break_start->diffInMinutes($ts->break_end);
                                            }
                                        }
                                        if ($ts->overtime_hours) {
                                            $totalMinutes += ($ts->overtime_hours * 60);
                                        }
                                        $hours = floor($totalMinutes / 60);
                                        $mins = $totalMinutes % 60;
                                    @endphp
                                    {{ sprintf('%02d:%02d', $hours, $mins) }}
                                </td>
                                <td>{{ $ts->leave_hours > 0 ? round($ts->leave_hours, 2) . 'h' : '-' }}</td>
                                <td>{{ $ts->overtime_hours > 0 ? round($ts->overtime_hours, 2) . 'h' : '-' }}</td>
                                <td class="text-end pe-3">
                                    <button class="btn btn-sm btn-outline-primary" wire:click="openEditModal({{ $ts->id }})">
                                        Modifica
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-3 text-muted">Nessun dato trovato per il periodo
                                    selezionato.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Monthly Report Section -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Report Mensile</h5>

            <div class="ms-auto me-3" x-data>
                <a x-bind:href="'{{ route('admin.timesheets.export') }}?month=' + $wire.reportMonth + '&year=' + $wire.reportYear"
                    target="_blank" class="btn btn-sm btn-success d-flex align-items-center gap-1">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export Excel
                </a>
            </div>

            <div class="d-flex align-items-center gap-2">
                <input type="text" wire:model.live.debounce.300ms="reportSearch" class="form-control form-control-sm"
                    placeholder="Cerca operatore...">

                <select wire:model.live="reportMonth" class="form-select form-select-sm" style="width: auto;">
                    @for($i = 1; $i <= 12; $i++)
                        <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                    @endfor
                </select>

                <select wire:model.live="reportYear" class="form-select form-select-sm" style="width: auto;">
                    @for($i = 2024; $i <= 2030; $i++)
                        <option value="{{ $i }}">{{ $i }}</option>
                    @endfor
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="">
                        <tr>
                            <th>Operatore</th>
                            <th>Giorni Presenza</th>
                            <th>Ore Permesso</th>
                            <th>Ore Straordinario</th>
                            <th>Totale Ore Lavorate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->monthlyReport as $row)
                            <tr>
                                <td class="fw-medium">{{ $row['user']->name }}</td>
                                <td>{{ $row['days_present'] }}</td>
                                <td>{{ round($row['leave_hours'], 2) }}h</td>
                                <td class="text-primary fw-bold">{{ $row['overtime_hours'] }}h</td>
                                <td class="fw-bold fs-6">{{ $row['total_hours'] }}h</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($showEditModal)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Modifica Presenza - {{ $editUserName }}</h5>
                        <button type="button" class="btn-close" wire:click="closeEditModal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Data</label>
                                <input type="date" class="form-control" wire:model="editDate">
                                @error('editDate') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label">Entrata</label>
                                <input type="time" class="form-control" wire:model="editEntryTime">
                                @error('editEntryTime') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label">Uscita</label>
                                <input type="time" class="form-control" wire:model="editExitTime">
                                @error('editExitTime') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-6">
                                <label class="form-label">Inizio Pausa</label>
                                <input type="time" class="form-control" wire:model="editBreakStart">
                                @error('editBreakStart') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-6">
                                <label class="form-label">Fine Pausa</label>
                                <input type="time" class="form-control" wire:model="editBreakEnd">
                                @error('editBreakEnd') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-7">
                                <label class="form-label">Tipo Permesso/Ferie</label>
                                <select class="form-select" wire:model.live="editLeaveType">
                                    <option value="">Nessuno</option>
                                    <option value="ferie">Ferie</option>
                                    <option value="permesso">Permesso orario</option>
                                    <option value="malattia">Malattia</option>
                                </select>
                                @error('editLeaveType') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-5">
                                <label class="form-label">Ore Permesso</label>
                                <input type="number" step="0.5" min="0" class="form-control" wire:model="editLeaveHours">
                                @error('editLeaveHours') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ore Straordinario</label>
                                <input type="number" step="0.5" min="0" class="form-control"
                                    wire:model="editOvertimeHours">
                                @error('editOvertimeHours') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-text mt-2">Orari in fuso Europe/Rome.</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeEditModal">Annulla</button>
                        <button type="button" class="btn btn-primary" wire:click="saveEdit">Salva modifiche</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
