<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3">Registro Presenze</h1>
    </div>

    <!-- Actions Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <h5 class="card-title mb-3">Azioni Giornaliere</h5>
            <div class="d-flex flex-wrap gap-2">
                <button wire:click="openActionModal('start_shift')" class="btn btn-secondary">
                    <i class="bi bi-play-circle me-1"></i> Inizio Turno
                </button>

                <button wire:click="openActionModal('start_break')" class="btn btn-secondary">
                    <i class="bi bi-pause-circle me-1"></i> Inizio Pausa
                </button>

                <button wire:click="openActionModal('end_break')" class="btn btn-secondary">
                    <i class="bi bi-play-circle-fill me-1"></i> Fine Pausa
                </button>

                <button wire:click="openActionModal('end_shift')" class="btn btn-secondary">
                    <i class="bi bi-stop-circle me-1"></i> Fine Turno
                </button>

                <div class="vr mx-2"></div>

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
                                    {{ $ts->entry_time ? $ts->entry_time->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->break_start ? $ts->break_start->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->break_end ? $ts->break_end->format('H:i') : '-' }}
                                </td>
                                <td>
                                    {{ $ts->exit_time ? $ts->exit_time->format('H:i') : '-' }}
                                </td>
                                <td>
                                    @if($ts->leave_hours > 0)
                                        {{ $ts->leave_hours }}h <small class="text-muted">({{ $ts->leave_type }})</small>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($ts->overtime_hours > 0)
                                        {{ $ts->overtime_hours }}h
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="fw-bold text-end px-4">
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
                        <div class="mb-3">
                            <label class="form-label">Orario</label>
                            <input type="time" wire:model="inputTime" class="form-control">
                        </div>

                        @if($actionType == 'leave')
                            <div class="mb-3">
                                <label class="form-label">Tipo Permesso</label>
                                <select wire:model="leaveType" class="form-select">
                                    <option value="">Seleziona...</option>
                                    <option value="ferie">Ferie</option>
                                    <option value="permesso">Permesso orario</option>
                                    <option value="malattia">Malattia</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Ore</label>
                                <input type="number" step="0.5" wire:model="leaveHours" class="form-control">
                            </div>
                        @endif

                        @if($actionType == 'overtime')
                            <div class="mb-3">
                                <label class="form-label">Ore Straordinario</label>
                                <input type="number" step="0.5" wire:model="overtimeHours" class="form-control">
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