<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use App\Models\CompanyWorkPhaseRate;
use App\Models\NetworkScope;
use App\Models\WorkPhase;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;
use App\Livewire\Concerns\HandlesWorkSuspensions;

class WorkEdit extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;
    use HandlesWorkSuspensions;

    public $work;
    public $operators;
    public $companies, $centrals, $workPhases, $networkScopes;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $network_scope_id, $description, $type, $phase, $work_phase_id, $company_assistant, $nroe, $wo_number,$unica_number, $notes, $expected_delivery_date;

    public function update(){
        $this->validate($this->rules());
        $validatedSuspensions = $this->validateStructuredSuspensions($this->work);

        DB::transaction(function () use ($validatedSuspensions): void {
            $this->work->update($this->workPayload());
            if ($this->canManageAdministrativeFields()) {
                $this->syncOperatorsPreservingAssignmentDates();
            }
            $this->syncStructuredSuspensions($this->work, $validatedSuspensions);
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

    protected function syncOperatorsPreservingAssignmentDates(): void
    {
        $operatorIds = collect($this->operator_id)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $existingAssignments = $this->work->users()
            ->newPivotStatement()
            ->where('work_id', $this->work->id)
            ->pluck('created_at', 'user_id');

        $now = Carbon::now();

        $syncData = $operatorIds
            ->mapWithKeys(fn (int $operatorId) => [
                $operatorId => [
                    'created_at' => $existingAssignments->get($operatorId, $now),
                    'updated_at' => $now,
                ],
            ])
            ->all();

        $this->work->users()->sync($syncData);
    }

    public function mount(Work $work){
        $this->work = $work->load('media');

        $this->companies = Company::all();
        $this->centrals = Central::all();
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
        $this->expected_delivery_date = $work->expected_delivery_date?->format('Y-m-d');

        $this->suspension_history = $work->suspension_history;
        $this->loadStructuredSuspensions($this->work);
        $this->files = [];
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();

    }

    protected function rules(): array
    {
        return array_merge($this->mediaUploadValidationRules(), [
            'expected_delivery_date' => 'nullable|date',
            'work_phase_id' => 'nullable|integer|exists:work_phases,id',
            'network_scope_id' => 'nullable|integer|exists:network_scopes,id',
        ]);
    }

    protected function workPayload(): array
    {
        $payload = array_merge([
            'company_id' => $this->company_id,
            'central_id' => $this->central_id,
            'network' => $this->network,
            'ao_cno' => $this->ao_cno,
            'ntw_scope' => $this->selectedNetworkScopeName(),
            'network_scope_id' => $this->network_scope_id ?: null,
            'description' => $this->description,
            'type' => $this->type,
            'phase' => $this->selectedWorkPhaseName(),
            'work_phase_id' => $this->work_phase_id ?: null,
            'company_assistant' => $this->company_assistant,
            'nroe' => $this->nroe,
            'wo_number' => $this->wo_number,
            'unica_number' => $this->unica_number,
            'notes' => $this->notes,
            'suspension_history' => $this->suspension_history,
        ], $this->accountingPayload());

        if ($this->canManageAdministrativeFields()) {
            $payload['status'] = $this->status;
            $payload['expected_delivery_date'] = $this->expected_delivery_date;
        }

        return $payload;
    }

    protected function accountingPayload(): array
    {
        $quantity = $this->accountingQuantity();

        if (! $this->company_id || ! $this->work_phase_id || $quantity === null) {
            return [
                'unit_rate' => null,
                'accounting_amount' => null,
            ];
        }

        $unitRate = CompanyWorkPhaseRate::query()
            ->where('company_id', $this->company_id)
            ->where('work_phase_id', $this->work_phase_id)
            ->value('unit_price');

        if ($unitRate === null) {
            return [
                'unit_rate' => null,
                'accounting_amount' => null,
            ];
        }

        return [
            'unit_rate' => $unitRate,
            'accounting_amount' => round((float) $unitRate * $quantity, 2),
        ];
    }

    protected function accountingQuantity(): ?float
    {
        if ($this->nroe === null) {
            return 1.0;
        }

        return is_numeric($this->nroe) ? (float) $this->nroe : null;
    }

    protected function selectedWorkPhaseName(): ?string
    {
        if (! $this->work_phase_id) {
            return $this->phase ?: null;
        }

        return WorkPhase::find($this->work_phase_id)?->name;
    }

    protected function resolveLegacyWorkPhaseId(?string $phase): ?int
    {
        $name = WorkPhase::normalizeLegacyName($phase);

        if (! $name) {
            return null;
        }

        return WorkPhase::where('name', $name)->value('id');
    }

    protected function resolveLegacyNetworkScopeId(?string $scope): ?int
    {
        $scope = trim((string) $scope);

        if ($scope === '') {
            return null;
        }

        return NetworkScope::where('name', $scope)->value('id');
    }

    protected function selectedNetworkScopeName(): ?string
    {
        if (! $this->network_scope_id) {
            return null;
        }

        return NetworkScope::find($this->network_scope_id)?->name;
    }

    protected function canManageAdministrativeFields(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'supervisor']);
    }

    public function render()
    {
        return view('livewire.work-edit');
    }
}
