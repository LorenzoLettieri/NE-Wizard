<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella GBX</h1>

                <div>
                    <a type="button" class="btn btn-success mx-2" href="{{ route('addGbx') }}">
                        <i class="bi bi-plus"></i>
                    </a>
                </div>

                {{-- Export button --}}
                @role('admin')
                <div>
                    <button type="button" class="btn btn-outline-primary mx-2" data-bs-toggle="modal"
                        data-bs-target="#exportGbxModal">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export
                    </button>
                </div>
                @endrole
            </div>

            <div class="col-12">
                <livewire:notification-center></livewire:notification-center>
                <livewire:GbxTable></livewire:GbxTable>
            </div>
        </div>
    </div>
</x-layout>

<!-- View Modal -->
<div class="modal fade" id="viewGbxModal" tabindex="-1" aria-labelledby="viewGbxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:view-gbx></livewire:view-gbx>
    </div>
</div>

<!-- edit Modal -->
<div class="modal fade" id="editGbxModal" tabindex="-1" aria-labelledby="editGbxModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <livewire:gbx-edit></livewire:gbx-edit>
    </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="exportGbxModal" tabindex="-1" aria-labelledby="exportGbxModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="GET" action="{{ route('exports.gbxes') }}" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exportGbxModalLabel">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Esporta GBX
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
            </div>

            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Campo data</label>
                    <select name="date_field" class="form-select" required>
                        <option value="created_at" selected>Data Creazione</option>
                        <option value="appointment_date">Data Appuntamento</option>
                        <option value="inspection_date">Data Sopralluogo</option>
                        <option value="verbal_date">Data Verbale</option>
                        <option value="obligation_date">Data Obbligo</option>
                        <option value="release_date">Data Rilascio</option>
                        <option value="project_date">Data Progetto</option>
                        <option value="speedark_date">Data Speedark</option>
                        <option value="permission_request_date">Data Rich. Permessi</option>
                        <option value="permission_obtain_date">Data Ott. Permessi</option>
                        <option value="cart_update_date">Data Agg. Cartellino</option>
                        <option value="date">Data</option>
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

                <div class="form-text mt-2">
                    Verranno esportati tutti i GBX filtrati per data.
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