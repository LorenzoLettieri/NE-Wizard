<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Lavorazioni</h1>
                
                {{-- Pulsante + già presente --}}
                <div>
                    <a type="button" class="btn btn-success mx-2" href="{{ route('addWork') }}">
                        <i class="bi bi-plus"></i>
                    </a>
                </div>

                {{-- NUOVO: Pulsante Export che apre la modale --}}
                <div>
                    <button type="button" class="btn btn-outline-primary mx-2" data-bs-toggle="modal" data-bs-target="#exportModal">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Export
                    </button>
                </div>
            </div>

            <div class="col-12">
                <livewire:notification-center></livewire:notification-center>
                <livewire:WorksTable></livewire:WorksTable>
            </div>
        </div>
    </div>
</x-layout>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:view-work></livewire:view-work>
  </div>
</div>

<!-- edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:edit-work></livewire:edit-work>
  </div>
</div>

{{-- NUOVO: Export Modal --}}
<div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="GET" action="{{ route('exports.works') }}" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportModalLabel">
            <i class="bi bi-file-earmark-spreadsheet"></i> Esporta Lavorazioni
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>

      <div class="modal-body">
        {{-- Campo data da usare per il filtro (se vuoi fissarlo, vedi NOTE sotto) --}}
        <div class="mb-3">
          <label class="form-label">Campo data</label>
          <select name="date_field" class="form-select" required>
            <option value="created_at" selected>Data Creazione</option>
            <option value="acception_date">Data Presa in carico</option>
            <option value="completion_date" >Data Fine Lavori</option>
            {{-- <option value="delivery_date">Data Consegna (delivery_date)</option> --}}
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
            Verranno esportate tutte le lavorazioni (incluse relazioni: impresa, centrale, operatori).
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

<style>
/* opzionale: nessuno stile specifico necessario */
</style>
