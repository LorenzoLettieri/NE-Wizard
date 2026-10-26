<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Company;
use App\Models\NetworkScope;
use App\Models\WorkPhase;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;
use App\Livewire\Concerns\HandlesMediaUploads;
use App\Livewire\Concerns\HandlesWorkFields;
use App\Livewire\Concerns\HandlesWorkSuspensions;

class EditWork extends Component
{
    use AuthorizesRequests;
    use WithFileUploads;
    use HandlesMediaUploads;
    use HandlesWorkFields;
    use HandlesWorkSuspensions;

    public $work;
    public $operators;

    public $companies;
    public $workPhases;
    public $networkScopes;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $network_scope_id, $description, $type, $phase, $work_phase_id, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne, $expected_delivery_date;
    public $go_live, $date_in_str, $date_out_str;
    public $daphne = '0';

    #[On('edit-work')]
    public function editWork($id)
    {
        $this->resetValidation();
        $this->work = Work::with(['media', 'users', 'workSuspensions'])->findOrFail($id);
        $this->authorize('update', $this->work);

        $this->company_id = $this->work->company_id;
        $this->central_id = $this->work->central_id;
        $this->operator_id = $this->work->users->pluck('id')->toArray();
        $this->status = $this->work->status;
        $this->network = $this->work->network;
        $this->ao_cno = $this->work->ao_cno;
        $this->ntw_scope = $this->work->ntw_scope;
        $this->network_scope_id = $this->work->network_scope_id ?? $this->resolveLegacyNetworkScopeId($this->work->ntw_scope);
        $this->description = $this->work->description;
        $this->type = $this->work->type;
        $this->phase = $this->work->phase;
        $this->work_phase_id = $this->work->work_phase_id ?? $this->resolveLegacyWorkPhaseId($this->work->phase);
        $this->company_assistant = $this->work->company_assistant;
        $this->nroe = $this->work->nroe;
        $this->wo_number = $this->work->wo_number;
        $this->unica_number = $this->work->unica_number;
        $this->notes = $this->work->notes;
        $this->daphne = $this->work->daphne ? '1' : '0';
        $this->tempo_daphne = $this->work->tempo_daphne;
        $this->go_live = $this->work->go_live === null ? '' : (int) $this->work->go_live;
        $this->date_in_str = $this->work->date_in_str;
        $this->date_out_str = $this->work->date_out_str;
        $this->expected_delivery_date = $this->work->expected_delivery_date?->format('Y-m-d');

        $this->suspension_history = $this->work->suspension_history;
        $this->loadStructuredSuspensions($this->work);
        $this->files = [];
        $this->initializeChunkedMediaUploads('work', $this->work->id);
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();
    }

    public function update()
    {
        $this->work = Work::with(['media', 'users', 'workSuspensions'])->findOrFail($this->work->getKey());
        $this->authorize('update', $this->work);
        $this->normalizeOperatorIdsInput();
        $this->validate($this->rules());
        $validatedSuspensions = $this->validateStructuredSuspensions($this->work);

        DB::transaction(function () use ($validatedSuspensions): void {
            $this->work->update($this->workPayload());
            if ($this->canManageAdministrativeFields()) {
                $this->syncOperatorsPreservingAssignmentDates();
            }
            $this->syncStructuredSuspensions($this->work, $validatedSuspensions);
            if ($this->canManageAdministrativeFields() && filled($this->status)) {
                $this->work->transitionToStatus($this->status, Carbon::now());
            }
        });

        $uploadedCount = 0;
        if ($this->files) {
            $uploadedCount = $this->persistUploadedFiles($this->work, 'works_media');
        }

        $removedCount = $this->commitPendingMediaRemovals($this->work);
        $this->work->refresh();
        $message = 'Lavorazione aggiornata con successo!';

        if ($uploadedCount > 0 || $removedCount > 0) {
            $details = [];

            if ($uploadedCount > 0) {
                $details[] = $uploadedCount === 1 ? '1 allegato caricato' : "{$uploadedCount} allegati caricati";
            }

            if ($removedCount > 0) {
                $details[] = $removedCount === 1 ? '1 allegato rimosso' : "{$removedCount} allegati rimossi";
            }

            $message .= ' ' . implode(', ', $details) . '.';
        }

        session()->flash('success', $message);
        $this->dispatch('workUpdated');
        $this->dispatch('notify', message: $message);
    }

    public function mount()
    {
        $this->companies = Company::all();
        $this->workPhases = WorkPhase::orderBy('name')->get();
        $this->networkScopes = NetworkScope::orderBy('name')->get();
        $this->operators = User::permission('get works')->get();
    }

    protected function rules(): array
    {
        $rules = array_merge($this->mediaUploadValidationRules(), $this->workFieldRules());

        if ($this->canManageAdministrativeFields()) {
            $rules['status'] = $this->statusRules($this->work?->status);
        }

        return $rules;
    }

    protected function workPayload(): array
    {
        $payload = array_merge([
            'company_id' => $this->blankToNull($this->company_id),
            'central_id' => $this->blankToNull($this->central_id),
            'network' => $this->network,
            'ao_cno' => $this->ao_cno,
            'ntw_scope' => $this->selectedNetworkScopeName(),
            'network_scope_id' => $this->network_scope_id ?: null,
            'description' => $this->description,
            'type' => $this->type,
            'phase' => $this->selectedWorkPhaseName(),
            'work_phase_id' => $this->work_phase_id ?: null,
            'company_assistant' => $this->company_assistant,
            'nroe' => $this->blankToNull($this->nroe),
            'wo_number' => $this->wo_number,
            'unica_number' => $this->unica_number,
            'notes' => $this->notes,
            'suspension_history' => $this->suspension_history,
            'tempo_daphne' => $this->tempo_daphne,
            'go_live' => $this->goLiveValue(),
            'date_in_str' => $this->date_in_str,
            'date_out_str' => $this->date_out_str,
            'daphne' => $this->daphneValue(),
        ], $this->accountingPayload());

        if ($this->canManageAdministrativeFields()) {
            $payload['status'] = $this->status;
            $payload['expected_delivery_date'] = $this->blankToNull($this->expected_delivery_date);
        }

        return $payload;
    }

    protected function refreshMediaAfterChunkedUpload(): void
    {
        $this->work?->load('media');
    }

    public function render()
    {
        return view('livewire.edit-work', [
            'statusOptions' => $this->statusOptions($this->work?->status),
        ]);
    }
}
