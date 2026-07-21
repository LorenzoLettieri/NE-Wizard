<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Registro Presenze</h1>
    </div>

    <!-- Actions Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Azioni Giornaliere</h5>

            <!-- Mobile Warning -->
            <div class="alert alert-warning d-md-none mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                La registrazione delle presenze è disponibile solo da postazione desktop.
            </div>

            @php
                $today = $this->todayTimesheet;
            @endphp
            <div class="d-flex flex-wrap gap-2">
                <!-- Shift/Break Actions: Desktop Only -->
                <div class="d-none d-md-flex flex-wrap gap-2">
                    <button wire:click="openActionModal('start_shift')" class="btn btn-secondary" @if($today && $today->effectiveShiftEntryTime()) disabled @endif>
                        <i class="bi bi-play-circle me-1"></i> Inizio Turno
                    </button>

                    <button wire:click="openActionModal('start_break')" class="btn btn-secondary" @if(!$today || ($today && $today->break_start) || ($today && !$today->effectiveShiftEntryTime()) || ($today && $today->exit_time))
                    disabled @endif>
                        <i class="bi bi-pause-circle me-1"></i> Inizio Pausa
                    </button>

                    <button wire:click="openActionModal('end_break')" class="btn btn-secondary" @if(!$today || ($today && $today->break_end) || ($today && !$today->break_start) || ($today && $today->exit_time))
                    disabled @endif>
                        <i class="bi bi-play-circle-fill me-1"></i> Fine Pausa
                    </button>

                    <button wire:click="openActionModal('end_shift')" class="btn btn-secondary" @if(!$today || ($today && $today->exit_time) || ($today && !$today->effectiveShiftEntryTime())) disabled @endif>
                        <i class="bi bi-stop-circle me-1"></i> Fine Turno
                    </button>

                    <div class="vr mx-2"></div>
                </div>

                <!-- Leave/Overtime Actions: All Devices -->
                <button wire:click="openActionModal('leave')" class="btn btn-outline-secondary">
                    <i class="bi bi-calendar-event me-1"></i> Inserisci Permesso
                </button>
                <button wire:click="openActionModal('overtime')" class="btn btn-outline-secondary">
                    <i class="bi bi-clock-history me-1"></i> Inserisci Straordinari
                </button>
            </div>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Riepilogo Settimanale</h5>
            <div class="d-flex align-items-center gap-2">
                <button wire:click="previousWeek" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-left"></i>
                </button>
                <span class="fw-bold">
                    {{ \Carbon\Carbon::parse($weekStartDate)->startOfWeek()->format('d/m/Y') }} -
                    {{ \Carbon\Carbon::parse($weekStartDate)->endOfWeek()->format('d/m/Y') }}
                </span>
                <button wire:click="nextWeek" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-chevron-right"></i>
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="">
                        <tr>
                            <th scope="col" class="px-4">Data</th>
                            <th scope="col">Inizio Turno</th>
                            <th scope="col">Inizio Pausa</th>
                            <th scope="col">Fine Pausa</th>
                            <th scope="col">Fine Turno</th>
                            <th scope="col">Permessi</th>
                            <th scope="col">Straordinari</th>
                            <th scope="col" class="text-end px-4">Totale Ore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->timesheets as $ts)
                            <tr>
                                <td class="px-4 fw-medium">{{ $ts->date->format('d/m/Y') }}</td>
                                <td>
                                    {{ $ts->effectiveShiftEntryTime() ? $ts->effectiveShiftEntryTime()->timezone('Europe/Rome')->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->break_start ? $ts->break_start->timezone('Europe/Rome')->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->break_end ? $ts->break_end->timezone('Europe/Rome')->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->exit_time ? $ts->exit_time->timezone('Europe/Rome')->format('H:i') : '-' }}
                                </td>
                                <td>
                                    @if($ts->leave_hours > 0)
                                        {{ round($ts->leave_hours, 2) }}h <small
                                            class="text-muted">({{ $ts->leave_type }})</small>
                                        @if($ts->effectiveLeaveStartTime())
                                            <small class="text-muted d-block">dalle {{ $ts->effectiveLeaveStartTime()->timezone('Europe/Rome')->format('H:i') }}</small>
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($ts->overtime_hours > 0)
                                        {{ round($ts->overtime_hours, 2) }}h
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold text-end px-4">
                                    @php
                                        $totalMinutes = 0;
                                        $shiftEntry = $ts->effectiveShiftEntryTime();
                                        if ($shiftEntry && $ts->exit_time) {
                                            $totalMinutes = $shiftEntry->diffInMinutes($ts->exit_time);
                                            if ($ts->break_start && $ts->break_end) {
                                                $totalMinutes -= round($ts->break_start->diffInMinutes($ts->break_end));
                                            }
                                        }
                                        if ($ts->overtime_hours) {
                                            $totalMinutes += ($ts->overtime_hours * 60);
                                        }

                                        $hours = floor($totalMinutes / 60);
                                        $mins = round($totalMinutes % 60);
                                    @endphp
                                    {{ sprintf('%02d:%02d', $hours, $mins) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    Nessuna attività registrata.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            @if($actionType == 'start_shift') Inizio Turno
                            @elseif($actionType == 'end_shift') Fine Turno
                            @elseif($actionType == 'start_break') Inizio Pausa
                            @elseif($actionType == 'end_break') Fine Pausa
                            @elseif($actionType == 'leave') Inserisci Permesso
                            @elseif($actionType == 'overtime') Inserisci Straordinari
                            @endif
                        </h5>
                        <button type="button" class="btn-close" wire:click="$set('showModal', false)"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @error('actionType')
                            <div class="alert alert-danger py-2">
                                <i class="bi bi-exclamation-circle me-1"></i>{{ $message }}
                            </div>
                        @enderror

                        @if($actionType == 'leave')
                            <div class="mb-3">
                                <label class="form-label">Tipo Permesso</label>
                                <select wire:model.live="leaveType" class="form-select @error('leaveType') is-invalid @enderror">
                                    <option value="">Seleziona...</option>
                                    <option value="ferie">Ferie</option>
                                    <option value="permesso">Permesso orario</option>
                                    <option value="malattia">Malattia</option>
                                </select>
                                @error('leaveType') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="row mb-3">
                                <div class="col-@if($leaveType == 'ferie') 6 @else 12 @endif">
                                    <label class="form-label">@if($leaveType == 'ferie') Data Inizio @else Data @endif</label>
                                    <input type="date" wire:model.live="selectedDate" class="form-control @error('selectedDate') is-invalid @enderror">
                                    @error('selectedDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @if($leaveType == 'ferie')
                                    <div class="col-6">
                                        <label class="form-label">Data Fine</label>
                                        <input type="date" wire:model.live="selectedEndDate" class="form-control @error('selectedEndDate') is-invalid @enderror"
                                            min="{{ $selectedDate }}">
                                        @error('selectedEndDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-text">
                                            @php
                                                $start = \Carbon\Carbon::parse($selectedDate);
                                                $end = \Carbon\Carbon::parse($selectedEndDate);
                                                $diff = $start->diffInDays($end) + 1;
                                            @endphp
                                            {{ $diff }} @if($diff == 1) giorno @else giorni @endif (max 15)
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if(in_array($leaveType, ['permesso', 'malattia']))
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Orario Inizio</label>
                                        <input type="time" wire:model="inputTime" class="form-control @error('inputTime') is-invalid @enderror">
                                        @error('inputTime') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Ore Totali</label>
                                        <input type="number" step="0.5" min="0.5" max="24" wire:model="leaveHours" class="form-control @error('leaveHours') is-invalid @enderror">
                                        @error('leaveHours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            @endif
                        @endif

                        @if($actionType == 'overtime')
                            <div class="mb-3">
                                <label class="form-label">Data</label>
                                <input type="date" wire:model.live="selectedDate" class="form-control @error('selectedDate') is-invalid @enderror">
                                @error('selectedDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ore Straordinario</label>
                                <input type="number" step="0.5" min="0.5" max="24" wire:model="overtimeHours" class="form-control @error('overtimeHours') is-invalid @enderror">
                                @error('overtimeHours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        @endif

                        @if(in_array($actionType, ['start_shift', 'end_shift', 'start_break', 'end_break']))
                            <div class="mb-3">
                                <label class="form-label">Orario</label>
                                <div class="h4 mb-0 text-primary">
                                    {{ $inputTime }}
                                </div>
                                <small class="text-muted">Verrà registrato l'orario attuale.</small>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            wire:click="$set('showModal', false)">Annulla</button>
                        <button type="button" class="btn btn-primary" wire:click="saveAction">Salva</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
