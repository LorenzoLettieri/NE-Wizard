<x-layout>
  <div class="container-fluid">
    <div class="row justify-content-center">
      <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
        <h1 class="text-center mx-3">Tabella Permessi Ente</h1>
        @hasanyrole('admin|permessi ente')
        <div>
          <a type="button" class="btn btn-success mx-2" href="{{ route('addPermesso') }}">
            <i class="bi bi-plus"></i>
          </a>
        </div>
        @endhasanyrole
        @role('admin')
        <div>
          <button type="button" class="btn btn-outline-primary mx-2" data-bs-toggle="modal"
            data-bs-target="#exportPermessiModal">
            <i class="bi bi-file-earmark-spreadsheet"></i> Export
          </button>
        </div>
        @endrole
      </div>

      <div class="col-12">
        <livewire:notification-center></livewire:notification-center>
        <livewire:permessi-ente-table></livewire:permessi-ente-table>
      </div>
    </div>
  </div>
</x-layout>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:permesso-ente-form :isShow="true"></livewire:permesso-ente-form>
  </div>
</div>

<!-- edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <livewire:permesso-ente-form :isShow="false" :isEdit="true"></livewire:permesso-ente-form>
  </div>
</div>

{{-- Export Modal --}}
<div class="modal fade" id="exportPermessiModal" tabindex="-1" aria-labelledby="exportPermessiModalLabel"
  aria-hidden="true">
  <div class="modal-dialog">
    <form method="GET" action="{{ route('exports.permessi-ente') }}" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exportPermessiModalLabel">
          <i class="bi bi-file-earmark-spreadsheet"></i> Esporta Permessi Ente
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Chiudi"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Campo data</label>
          <select name="date_field" class="form-select" required>
            <option value="created_at" selected>Data Creazione</option>
            <option value="consegna">Consegna</option>
            <option value="data_fl">Data FL</option>
            <option value="data_ra">Data RA</option>
            <option value="evaso_dal_dl">Evaso dal DL</option>
            <option value="mese_saldo">Mese Saldo</option>
            <option value="acception_date">Data PiC</option>
            <option value="delivery_date">Data Consegna</option>
            <option value="completion_date">Data Completamento</option>
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
          Verranno esportati tutti i Permessi Ente filtrati per data.
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