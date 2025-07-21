<div>
    {{-- Do your work, then step back. --}}
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="viewModalLabel">Modifica Lavorazione</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="container mt-4">
    
{{-- Stop trying to control. --}}
<div class="col-12 col-md-12 my-3">
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
            <button type="submit" class="btn btn-primary ">Aggiorna Lavorazione</button>
             <button type="button" class="btn btn-secondary mx-5" data-bs-dismiss="modal">Close</button>

        </div>
        
    </form>
</div>


</div>
      </div>
</div>

</div>
