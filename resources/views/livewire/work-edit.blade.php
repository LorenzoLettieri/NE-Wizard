
{{-- Stop trying to control. --}}
<div class="col-12 col-md-6 my-3">
    @if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    @if (session('success'))

        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    <form wire:submit="update" method="POST">
        @csrf
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 col-md-4 mb-4">
                    <label for="company" class="form-label">Compagnia</label>
                    <select wire:model="company_id" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 mb-4">
                    <label for="status" class="form-label">Stato Lavorazione:</label>
                    <select wire:model="status" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        <option value="Da Lavorare">Da Lavorare</option>
                        <option value="In Lavorazione">In Lavorazione</option>
                        <option value="Sospeso">Sospeso</option>
                        <option value="Attesa Fine Lavori">Attesa Fine Lavori</option>
                        <option value="KO">KO</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 mb-4">
                    <label for="" class="form-label">Centrale:</label>
                    <select wire:model="central_id" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        @foreach($centrals as $central)
                            <option value="{{ $central->id }}">{{ $central->central }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="row" >
                <div class="col-12 col-md-4 mb-4">
                    <label for="network" class="form-label">Codice NTW:</label>
                    <input type="network" class="form-control" id="network" wire:model="network">
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <label for="ao_cno" class="form-label">AO/CNO:</label>
                    <input type="ao_cno" class="form-control" id="ao_cno" wire:model="ao_cno">
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <label for="" class="form-label">Ambito NTW:</label>
                    <select wire:model="ntw_scope" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        <option value="FTTH">FTTH</option>
                        <option value="FTTH PTE">FTTH PTE</option>
                        <option value="FTTH PNRR">FTTH PNRR</option>
                        <option value="5G">5G</option>
                        <option value="REACTIVE">REACTIVE</option>
                        <option value="INCREMENTALE">INCREMENTALE</option>
                        <option value="DESATURAZIONE">DESATURAZIONE</option>
                        <option value="NGAN">NGAN</option>
                        <option value="GIUNZIONE">GIUNZIONE</option>
                        <option value="SUB-LOOP">SUB-LOOP</option>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-12 mb-4">
                    <label for="description" class="form-label">Descrizione:</label>
                    <input type="description" class="form-control" id="description" wire:model="description" placeholder="Appunti Aggiuntivi">
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <label for="type" class="form-label">RAME/OTTICO:</label>
                    <select wire:model="type" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        <option value="RAME">RAME</option>
                        <option value="OTTICO">OTTICO</option>
                    </select>
                </div>
            </div>
            <div class="row mb-5 pb-2">
                <div class="col-12 col-md-4 mb-4">
                    <label for="phase" class="form-label">Fase Lavoro:</label>
                    <select wire:model="phase" class="form-select tom-select">
                        <option value="">-- Seleziona --</option>
                        <option value="FASE 1">FASE 1</option>
                        <option value="FASE 2">FASE 2</option>
                        <option value="AGGIORNAMENTO">AGGIORNAMENTO</option>
                        <option value="MODIFICA">MODIFICA</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <label for="company_assistant" class="form-label">Assistente Impresa:</label>
                    <input type="company_assistant" class="form-control" id="company_assistant" wire:model="company_assistant" placeholder="">
                </div>
                <div class="col-12 col-md-4 mb-4">
                    <label for="nroe" class="form-label">N ROE:</label>
                    <input type="nroe" class="form-control" id="nroe" wire:model="nroe" placeholder="">
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <label for="wo_number" class="form-label">WO Number:</label>
                    <input type="wo_number" class="form-control" id="wo_number" wire:model="wo_number" placeholder="">
                </div>
                <div class="col-12 col-md-6 mb-4">
                    <label for="unica_number" class="form-label">UNICA Number:</label>
                    <input type="unica_number" class="form-control" id="unica_number" wire:model="unica_number" placeholder="">
                </div>
                <div class="col-12 col-md-12 mb-4">
                    <label for="notes" class="form-label">Note:</label>
                    <textarea class="form-control" id="notes" rows="5" wire:model="notes"></textarea>
                </div>
                <div class="col-12 col-md-12 mb-4">
                    <label for="suspension_history" class="form-label">Storico Sospensioni:</label>
                    <textarea class="form-control" id="suspension_history" rows="5" wire:model="suspension_history"></textarea>
                </div>
                @role('admin|supervisor')
                    @include('livewire.partials.work-suspensions-editor')
                @endrole
                  <div class="col-12 col-md-12 mb-4">
                    <label for="phase" class="form-label">Assegna Lavorazione ad Operatore:</label>
                    <select wire:model="operator_id" class="form-select tom-select-multiple" multiple>
                            <option value="">-- Seleziona --</option>
                        @foreach ($operators as $operator)
                            <option value="{{$operator->id}}">{{$operator->name}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <h4 class="mt-4 mb-4 border-bottom pb-2">Media</h4>
            <div class="row g-3 mb-4">
                <div class="col-12">
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
                    <div class="col-12">
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

                @if($work && $work->media->count() > 0)
                    <div class="col-12">
                        <label class="form-label fw-bold">Allegati Esistenti</label>
                        @if(count($pendingMediaRemovalIds) > 0)
                            <div class="alert alert-warning mt-2 mb-3">
                                Gli allegati segnati verranno eliminati solo dopo il salvataggio.
                            </div>
                        @endif
                        <ul class="list-group">
                            @foreach($work->media as $media)
                                <li class="list-group-item d-flex justify-content-between align-items-center {{ in_array($media->id, $pendingMediaRemovalIds, true) ? 'list-group-item-warning' : '' }}">
                                    <span><i class="bi bi-file-earmark text-secondary me-2"></i>{{ $media->file_name }}</span>
                                    <div class="d-flex gap-2">
                                        <a href="{{ Storage::url($media->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-download"></i> Scarica
                                        </a>
                                        <button type="button" class="btn btn-sm {{ in_array($media->id, $pendingMediaRemovalIds, true) ? 'btn-outline-secondary' : 'btn-outline-danger' }}" wire:click="toggleMediaRemoval({{ $media->id }})">
                                            <i class="bi {{ in_array($media->id, $pendingMediaRemovalIds, true) ? 'bi-arrow-counterclockwise' : 'bi-trash' }}"></i>
                                            {{ in_array($media->id, $pendingMediaRemovalIds, true) ? 'Annulla' : 'Segna Per Eliminazione' }}
                                        </button>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
            <button type="submit" class="btn btn-primary">Aggiorna Lavorazione</button>
        </div>
        
    </form>
</div>
