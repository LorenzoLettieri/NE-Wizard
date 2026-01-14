<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 mt-3 d-flex justify-content-center align-items-center">
                <h1 class="text-center mx-3">Tabella Permessi Ente</h1>
                
                {{-- Pulsante + già presente --}}
                @unlessrole('permessi ente')
                <div>
                    <a type="button" class="btn btn-success mx-2" href="{{ route('addPermesso') }}">
                        <i class="bi bi-plus"></i>
                    </a>
                </div>
                @endunlessrole
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

<style>
/* opzionale: nessuno stile specifico necessario */
</style>
