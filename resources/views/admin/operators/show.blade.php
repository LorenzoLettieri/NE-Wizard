<x-layout>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10 mt-3">
                <div class="alert alert-warning border-0 shadow-sm mb-4">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                        <div>
                            <div class="fw-semibold">Stai visualizzando la bacheca operatore di {{ $operator->name }}</div>
                            <div class="small mb-0">
                                Modalita amministratore in sola lettura. Questa vista non cambia la sessione e termina quando lasci la pagina.
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('accounts-table') }}" class="btn btn-outline-dark btn-sm">Torna agli account</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-10 mb-4">
                <livewire:operator-table :target-operator-id="$operator->id" :read-only-mode="true" />
            </div>

            <div class="col-12 col-xl-10">
                <livewire:operator-stats :locked-operator-id="$operator->id" :hide-operator-filter-when-locked="true" />
            </div>
        </div>
    </div>

    <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <livewire:view-work></livewire:view-work>
        </div>
    </div>
</x-layout>
