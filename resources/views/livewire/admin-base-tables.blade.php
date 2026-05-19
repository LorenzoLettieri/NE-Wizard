<div>
    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'Central' ? 'active' : '' }}" 
               wire:click="setTab('Central')">Centrali</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'Comune' ? 'active' : '' }}" 
               wire:click="setTab('Comune')">Comuni</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'Regione' ? 'active' : '' }}" 
               wire:click="setTab('Regione')">Regioni</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'Company' ? 'active' : '' }}" 
               wire:click="setTab('Company')">Company</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'WorkPhase' ? 'active' : '' }}"
               wire:click="setTab('WorkPhase')">Fasi Lavoro</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'NetworkScope' ? 'active' : '' }}"
               wire:click="setTab('NetworkScope')">Ambiti Network</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'CompanyWorkPhaseRate' ? 'active' : '' }}"
               wire:click="setTab('CompanyWorkPhaseRate')">Matrice Prezzi</a>
        </li>
        <li class="nav-item">
            <a class="nav-link cursor-pointer {{ $activeTab === 'CompanyDecommissioningRate' ? 'active' : '' }}"
               wire:click="setTab('CompanyDecommissioningRate')">Matrice Prezzi Decommissioning</a>
        </li>
    </ul>

    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h4>
            @if($activeTab === 'CompanyWorkPhaseRate')
                Matrice Prezzi
            @elseif($activeTab === 'CompanyDecommissioningRate')
                Matrice Prezzi Decommissioning
            @else
                {{ $activeTab }}
            @endif
        </h4>
        <div class="d-flex align-items-center gap-2">
            @if(! in_array($activeTab, ['CompanyWorkPhaseRate', 'CompanyDecommissioningRate'], true))
                <input
                    type="text"
                    class="form-control"
                    style="min-width: 280px;"
                    wire:model.blur="search"
                    placeholder="{{ $searchPlaceholder }}"
                >
            @endif
            @if(! in_array($activeTab, ['CompanyWorkPhaseRate', 'CompanyDecommissioningRate'], true))
                <button class="btn btn-success text-nowrap" wire:click="openCreateModal">
                    <i class="bi bi-plus-lg me-1"></i> Aggiungi Nuovo
                </button>
            @endif
        </div>
    </div>

    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if($activeTab === 'CompanyWorkPhaseRate')
        <div class="alert alert-info">
            Inserisci il valore unitario per ogni combinazione Company/Fase Lavoro. La lavorazione salver&agrave; l'importo calcolato come tariffa unitaria &times; N.Roe.
        </div>

        <div class="table-responsive bg-body-tertiary rounded shadow-sm">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" class="text-nowrap">Company</th>
                        @foreach($workPhasesForRates as $phase)
                            <th scope="col" class="text-nowrap text-center">{{ $phase->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($companiesForRates as $company)
                        <tr>
                            <th scope="row" class="text-nowrap">{{ $company->name }}</th>
                            @foreach($workPhasesForRates as $phase)
                                <td style="min-width: 120px;">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">&euro;</span>
                                        <input
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="form-control text-end"
                                            wire:model.blur="rateValues.{{ $company->id }}.{{ $phase->id }}"
                                            wire:blur="saveRate({{ $company->id }}, {{ $phase->id }})"
                                        >
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, $workPhasesForRates->count() + 1) }}" class="text-center py-4 text-muted">
                                Aggiungi almeno una company e una fase lavoro per compilare la matrice.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif($activeTab === 'CompanyDecommissioningRate')
        <div class="alert alert-info">
            Inserisci i valori unitari PROG e NE per ogni combinazione Company/voce decommissioning. Il decommissioning salver&agrave; gli importi calcolati come tariffa unitaria &times; quantit&agrave;.
        </div>

        <div class="table-responsive bg-body-tertiary rounded shadow-sm">
            <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col" class="text-nowrap">Company</th>
                        @foreach($decommissioningRateItems as $itemIndex => $itemLabel)
                            <th scope="col" class="text-nowrap text-center">{{ $itemLabel }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($companiesForRates as $company)
                        <tr>
                            <th scope="row" class="text-nowrap">{{ $company->name }}</th>
                            @foreach($decommissioningRateItems as $itemIndex => $itemLabel)
                                <td style="min-width: 190px;">
                                    <div class="vstack gap-1">
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">PROG &euro;</span>
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="form-control text-end"
                                                wire:model.blur="decommissioningRateValues.{{ $company->id }}.{{ $itemIndex }}.prog_price"
                                                wire:blur="saveDecommissioningRate({{ $company->id }}, {{ $itemIndex }})"
                                            >
                                        </div>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">NE &euro;</span>
                                            <input
                                                type="number"
                                                min="0"
                                                step="0.01"
                                                class="form-control text-end"
                                                wire:model.blur="decommissioningRateValues.{{ $company->id }}.{{ $itemIndex }}.ne_price"
                                                wire:blur="saveDecommissioningRate({{ $company->id }}, {{ $itemIndex }})"
                                            >
                                        </div>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1, count($decommissioningRateItems) + 1) }}" class="text-center py-4 text-muted">
                                Aggiungi almeno una company per compilare la matrice decommissioning.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
    <!-- Table Section -->
    <div class="table-responsive bg-body-tertiary rounded shadow-sm">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th scope="col">ID</th>
                    
                    @if($activeTab === 'Central')
                        <th scope="col">Nome Centrale</th>
                        <th scope="col">Regione</th>
                    @elseif($activeTab === 'Comune')
                        <th scope="col">Progressivo</th>
                        <th scope="col">Codice</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Location</th>
                        <th scope="col">Codice Catasto</th>
                        <th scope="col">Regione</th>
                    @elseif($activeTab === 'Regione')
                        <th scope="col">Nome</th>
                    @elseif($activeTab === 'Company')
                        <th scope="col">Nome Company</th>
                    @elseif($activeTab === 'WorkPhase')
                        <th scope="col">Nome Fase Lavoro</th>
                    @elseif($activeTab === 'NetworkScope')
                        <th scope="col">Nome Ambito Network</th>
                    @endif

                    <th scope="col" class="text-end">Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $row)
                    <tr>
                        <th scope="row">{{ $row->id }}</th>
                        
                        @if($activeTab === 'Central')
                            <td>{{ $row->central }}</td>
                            <td>{{ $row->region }}</td>
                        @elseif($activeTab === 'Comune')
                            <td>{{ $row->comune_progressive }}</td>
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->location }}</td>
                            <td>{{ $row->catasto_code }}</td>
                            <td>{{ $row->regione?->nome ?? '-' }}</td>
                        @elseif($activeTab === 'Regione')
                            <td>{{ $row->nome }}</td>
                        @elseif($activeTab === 'Company')
                            <td>{{ $row->name }}</td>
                        @elseif($activeTab === 'WorkPhase')
                            <td>{{ $row->name }}</td>
                        @elseif($activeTab === 'NetworkScope')
                            <td>{{ $row->name }}</td>
                        @endif

                        <td class="text-end">
                            <button class="btn btn-sm btn-primary" wire:click="openEditModal({{ $row->id }})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger ms-1" wire:click="deleteRecord({{ $row->id }})" wire:confirm="Sei sicuro di voler eliminare questo record?">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="text-center py-4 text-muted">
                            Nessun record trovato.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($records, 'links'))
        <div class="mt-3">
            {{ $records->links() }}
        </div>
    @endif
    @endif

    <!-- Modal Form -->
    @if($showModal)
        <div class="modal fade show d-block" style="background-color: rgba(0,0,0,0.5);" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <form wire:submit.prevent="saveRecord">
                        <div class="modal-header">
                            <h5 class="modal-title">
                                {{ $isEditing ? 'Modifica ' . $activeTab : 'Nuovo record in ' . $activeTab }}
                            </h5>
                            <button type="button" class="btn-close" wire:click="resetModal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            
                            @if($activeTab === 'Central')
                                <div class="mb-3">
                                    <label class="form-label">Nome Centrale *</label>
                                    <input type="text" class="form-control" wire:model="formData.central" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Regione</label>
                                    <input type="text" class="form-control" wire:model="formData.region">
                                </div>
                            
                            @elseif($activeTab === 'Comune')
                                <div class="mb-3">
                                    <label class="form-label">Progressivo Comune *</label>
                                    <input type="text" class="form-control" wire:model="formData.comune_progressive" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nome Comune *</label>
                                    <input type="text" class="form-control" wire:model="formData.name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Codice *</label>
                                    <input type="text" class="form-control" wire:model="formData.code" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Location</label>
                                    <input type="text" class="form-control" wire:model="formData.location">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Codice Catasto</label>
                                    <input type="text" class="form-control" wire:model="formData.catasto_code">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Regione</label>
                                    <select class="form-select" wire:model="formData.regione_id">
                                        <option value="">Nessuna regione</option>
                                        @foreach($regioni as $id => $nome)
                                            <option value="{{ $id }}">{{ $nome }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Sovracomune</label>
                                    <input type="text" class="form-control" wire:model="formData.sovracomune">
                                </div>

                            @elseif($activeTab === 'Regione')
                                <div class="mb-3">
                                    <label class="form-label">Nome *</label>
                                    <input type="text" class="form-control" wire:model="formData.nome" required>
                                </div>

                            @elseif($activeTab === 'Company')
                                <div class="mb-3">
                                    <label class="form-label">Nome Company *</label>
                                    <input type="text" class="form-control" wire:model="formData.name" required>
                                </div>
                            @elseif($activeTab === 'WorkPhase')
                                <div class="mb-3">
                                    <label class="form-label">Nome Fase Lavoro *</label>
                                    <input type="text" class="form-control" wire:model="formData.name" required>
                                </div>
                            @elseif($activeTab === 'NetworkScope')
                                <div class="mb-3">
                                    <label class="form-label">Nome Ambito Network *</label>
                                    <input type="text" class="form-control" wire:model="formData.name" required>
                                </div>
                            @endif

                            @if($errors->any())
                                <div class="alert alert-danger mt-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" wire:click="resetModal">Annulla</button>
                            <button type="submit" class="btn btn-primary">Salva</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <style>
        .cursor-pointer {
            cursor: pointer;
        }
    </style>
</div>
