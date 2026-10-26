<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Company;
use App\Models\NetworkScope;
use App\Models\WorkPhase;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;
use App\Livewire\Concerns\HandlesWorkFields;
use App\Livewire\Concerns\HandlesWorkSuspensions;

class WorkEdit extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;
    use HandlesWorkFields;
    use HandlesWorkSuspensions;

    public $work;
    public $operators;
    public $companies, $workPhases, $networkScopes;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $network_scope_id, $description, $type, $phase, $work_phase_id, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne, $expected_delivery_date;

    public $daphne = '0';

    public function update(){
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
        $this->redirect(route('works-table'));
    }

    public function mount(Work $work){
        $this->work = $work->load('media');

        $this->companies = Company::all();
        $this->workPhases = WorkPhase::orderBy('name')->get();
        $this->networkScopes = NetworkScope::orderBy('name')->get();
        $this->operators = User::permission('get works')->get();

        $this->company_id = $work->company_id;
        $this->central_id = $work->central_id;
        $this->operator_id = $work->users->pluck('id')->toArray();
        $this->status = $work->status;
        $this->network = $work->network;
        $this->ao_cno = $work->ao_cno;
        $this->ntw_scope = $work->ntw_scope;
        $this->network_scope_id = $work->network_scope_id ?? $this->resolveLegacyNetworkScopeId($work->ntw_scope);
        $this->description = $work->description;
        $this->type = $work->type;
        $this->phase = $work->phase;
        $this->work_phase_id = $work->work_phase_id ?? $this->resolveLegacyWorkPhaseId($work->phase);
        $this->company_assistant = $work->company_assistant;
        $this->nroe = $work->nroe;
        $this->wo_number = $work->wo_number;
        $this->unica_number = $work->unica_number;
        $this->notes = $work->notes;
        $this->daphne = $work->daphne ? '1' : '0';
        $this->tempo_daphne = $work->tempo_daphne;
        $this->expected_delivery_date = $work->expected_delivery_date?->format('Y-m-d');

        $this->suspension_history = $work->suspension_history;
        $this->loadStructuredSuspensions($this->work);
        $this->files = [];
        $this->initializeChunkedMediaUploads('work', $work->id);
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();

    }

    protected function rules(): array
    {
        // Il form non mostra Go Live: la regola condivisa non serve qui
        $rules = array_merge($this->mediaUploadValidationRules(), $this->workFieldRules());
        unset($rules['go_live']);

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
            'daphne' => $this->daphneValue(),
            'tempo_daphne' => $this->tempo_daphne,
            'suspension_history' => $this->suspension_history,
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
        return view('livewire.work-edit', [
            'statusOptions' => $this->statusOptions($this->work?->status),
        ]);
    }
}
