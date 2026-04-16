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
    </ul>

    <!-- Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>{{ $activeTab }}</h4>
        <button class="btn btn-success" wire:click="openCreateModal">
            <i class="bi bi-plus-lg me-1"></i> Aggiungi Nuovo
        </button>
    </div>

    @if (session('message'))
        <div class="alert alert-success">
            {{ session('message') }}
        </div>
    @endif

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
                        <th scope="col">Codice</th>
                        <th scope="col">Nome</th>
                        <th scope="col">Location</th>
                        <th scope="col">Codice Catasto</th>
                        <th scope="col">Regione</th>
                    @elseif($activeTab === 'Regione')
                        <th scope="col">Nome</th>
                    @elseif($activeTab === 'Company')
                        <th scope="col">Nome Company</th>
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
                            <td>{{ $row->code }}</td>
                            <td>{{ $row->name }}</td>
                            <td>{{ $row->location }}</td>
                            <td>{{ $row->catasto_code }}</td>
                            <td>{{ $row->region }}</td>
                        @elseif($activeTab === 'Regione')
                            <td>{{ $row->nome }}</td>
                        @elseif($activeTab === 'Company')
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
                                    <label class="form-label">Nome Comune *</label>
                                    <input type="text" class="form-control" wire:model="formData.name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Codice</label>
                                    <input type="text" class="form-control" wire:model="formData.code">
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
                                    <input type="text" class="form-control" wire:model="formData.region">
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
