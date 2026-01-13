<div class="col-12 col-md-10 mx-auto my-4 {{ $isShow || $isEdit ? 'modal-content' : '' }}">
    <!-- ... header ... -->

    <form wire:submit.prevent="save">
        <div class="container-fluid">
            {{-- PRIMA RIGA --}}
            <div class="row mb-3">
                <div class="col-md-3 mb-3">
                    <label class="form-label">Network</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" class="form-control" wire:model="network">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Consegna</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="date" class="form-control" wire:model="consegna">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Progetto</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="text" class="form-control" wire:model="progetto">
                </div>

                <div class="col-md-3 mb-3">
                    <label class="form-label">Via</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="text" class="form-control" wire:model="via">
                </div>
            </div>

            {{-- RELAZIONI --}}
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label" >Regione</label>
                    <select {{ $isShow ? 'disabled' : '' }} class="form-select tom-select" wire:model="regione_id">
                        <option value="">-- Seleziona --</option>
                        @foreach ($regioni as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Comune</label>
                    <select {{ $isShow ? 'disabled' : '' }} class="form-select tom-select" wire:model="comune_id">
                        <option value="">-- Seleziona --</option>
                        @foreach ($comuni as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Centrale</label>
                    <select {{ $isShow ? 'disabled' : '' }} class="form-select tom-select" wire:model="central_id">
                        <option value="">-- Seleziona --</option>
                        @foreach ($centrali as $id => $nome)
                            <option value="{{ $id }}">{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- DESCRIZIONE --}}
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label">Descrizione</label>
                    <textarea {{ $isShow ? 'readonly' : '' }} class="form-control" rows="3" wire:model="descrizione"></textarea>
                </div>
            </div>

            {{-- CAMPI SI/NO --}}
            <div class="row mb-3">
                @foreach ([
                    'ap_chiusini' => 'AP Chiusini',
                    'scavo_100' => 'Scavo fino a 100 metri',
                    'urgente' => 'Urgente',
                    'ordinaria' => 'Ordinaria',
                    'fine_lavori' => 'Fine Lavori',
                    'ra' => 'RA',
                ] as $field => $label)
                    <div class="col-md-4 mb-3">
                        <label class="form-label">{{ $label }}</label>
                        <select {{ $isShow ? 'disabled' : '' }} class="form-select" wire:model="{{ $field }}">
                            <option value="">-- Seleziona --</option>
                            <option value="1">SI</option>
                            <option value="0">NO</option>
                        </select>
                    </div>
                @endforeach
            </div>

            {{-- ALTRI CAMPI NUMERICI --}}
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Numero Chiusini</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" class="form-control" wire:model="num_chiusini">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Quote aggiuntive</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" class="form-control" wire:model="quote_aggiuntive">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Mese Saldo</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="month" class="form-control" wire:model="mese_saldo">
                </div>
            </div>

            {{-- DATE --}}
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Data FL</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="date" class="form-control" wire:model="data_fl">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Data RA</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="date" class="form-control" wire:model="data_ra">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Evaso dal DL</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="date" class="form-control" wire:model="evaso_dl">
                </div>
            </div>

            <div class="col-12 col-md-12 mb-4">
                <label for="phase" class="form-label">Assegna Lavorazione ad Operatore:</label>
                <select {{ $isShow ? 'disabled' : '' }} wire:model="operator_id" class="form-select tom-select-multiple">
                    <option value="">-- Seleziona --</option>
                    @foreach ($operators as $operator)
                        <option value="{{$operator->id}}">{{$operator->name}}</option>
                    @endforeach
                </select>
            </div>

            @role('admin')
            {{-- ADMIN ONLY FIELDS --}}
            <div class="row mb-3">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Al DL (€)</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" step="0.01" class="form-control" wire:model="al_dl">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">A NE (€)</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" step="0.01" class="form-control" wire:model="a_ne">
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">Delta (€)</label>
                    <input {{ $isShow ? 'readonly' : '' }} type="number" step="0.01" class="form-control" wire:model="delta">
                </div>
            </div>

            <div class="row mb-4">
                @for ($i = 1; $i <= 4; $i++)
                    <div class="col-md-3 mb-3">
                        <label class="form-label">VDC{{ $i }}</label>
                        <input {{ $isShow ? 'readonly' : '' }} type="number" class="form-control" wire:model="vdc{{ $i }}">
                    </div>
                @endfor
            </div>
            @endrole

            <div class="row">
                <div class="col text-end">
                    @if(!$isShow)
                    <button type="submit" class="btn btn-primary px-4">
                        {{ $isEdit ? 'Aggiorna Permesso' : 'Crea Permesso' }}
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </form>

</div>