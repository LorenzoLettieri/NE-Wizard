<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Decommissioning</h1>

                <div>
                    <a type="button" class="btn btn-success mx-2" href="{{ route('addDecommissioning') }}">
                        <i class="bi bi-plus"></i>
                    </a>
                </div>

                @role('admin')
                    <div>
                        <button type="button" class="btn btn-outline-primary mx-2" data-bs-toggle="modal" data-bs-target="#exportDecommissioningModal">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export
                        </button>
                    </div>
                @endrole
            </div>

            @if (session('success'))
                <div class="col-12 col-xl-10">
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <div class="col-12">
                <livewire:decommissioning-table></livewire:decommissioning-table>
            </div>
        </div>
    </div>
</x-layout>

<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:decommissioning-form :isShow="true"></livewire:decommissioning-form>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:decommissioning-form :isShow="false" :isEdit="true"></livewire:decommissioning-form>
    </div>
</div>

@role('admin')
    <div class="modal fade" id="exportDecommissioningModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="GET" action="{{ route('exports.decommissionings') }}" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Esporta Decommissioning
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Campo data</label>
                        <select name="date_field" class="form-select" required>
                            <option value="created_at" selected>Data Inserimento</option>
                            <option value="fl_permesso">FL Permesso</option>
                            <option value="data_il">Data IL</option>
                            <option value="data_fl">Data FL</option>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Dal</label>
                            <input type="date" name="start" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Al</label>
                            <input type="date" name="end" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annulla</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-download"></i> Scarica Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
@endrole
