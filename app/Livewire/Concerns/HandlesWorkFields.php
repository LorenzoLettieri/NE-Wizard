<?php

namespace App\Livewire\Concerns;

use App\Models\CompanyWorkPhaseRate;
use App\Models\NetworkScope;
use App\Models\Work;
use App\Models\WorkPhase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

// Logica condivisa da WorkForm, EditWork e WorkEdit
trait HandlesWorkFields
{
    protected function workFieldRules(): array
    {
        return [
            'company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')],
            'central_id' => ['nullable', 'integer', Rule::exists('centrals', 'id')],
            'nroe' => ['nullable', 'integer', 'min:0'],
            'daphne' => ['required', 'boolean'],
            'go_live' => ['nullable', 'boolean'],
            'operator_id' => ['nullable', 'array'],
            'operator_id.*' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'expected_delivery_date' => 'nullable|date',
            'work_phase_id' => 'nullable|integer|exists:work_phases,id',
            'network_scope_id' => 'nullable|integer|exists:network_scopes,id',
        ];
    }

    /**
     * Stati selezionabili. In creazione quelli del form; in modifica tutti gli stati
     * più quello già salvato, così una lavorazione con stato fuori lista resta salvabile.
     */
    protected function statusOptions(?string $currentStatus = null, bool $creating = false): array
    {
        $options = $creating ? Work::FORM_STATUSES : Work::STATUSES;

        if (filled($currentStatus) && ! in_array($currentStatus, $options, true)) {
            $options[] = $currentStatus;
        }

        return $options;
    }

    protected function statusRules(?string $currentStatus = null, bool $creating = false): array
    {
        // Una lavorazione già salvata senza stato può restare così: non si obbliga a sceglierne uno
        $presence = ($creating || filled($currentStatus)) ? 'required' : 'nullable';

        return [$presence, 'string', Rule::in($this->statusOptions($currentStatus, $creating))];
    }

    // Il vecchio form di creazione inviava un solo operatore: si accettano entrambe le forme
    protected function normalizeOperatorIdsInput(): void
    {
        if (is_array($this->operator_id)) {
            return;
        }

        $this->operator_id = filled($this->operator_id) ? [$this->operator_id] : [];
    }

    protected function blankToNull(mixed $value): mixed
    {
        return $value === '' ? null : $value;
    }

    protected function daphneValue(): bool
    {
        return filter_var($this->daphne, FILTER_VALIDATE_BOOLEAN);
    }

    protected function goLiveValue(): ?bool
    {
        if ($this->go_live === null || $this->go_live === '') {
            return null;
        }

        return filter_var($this->go_live, FILTER_VALIDATE_BOOLEAN);
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
        // Un N ROE vuoto vale come assente: si conteggia 1, come per i valori null
        $nroe = $this->blankToNull($this->nroe);

        if ($nroe === null) {
            return 1.0;
        }

        return is_numeric($nroe) ? (float) $nroe : null;
    }

    protected function selectedWorkPhaseName(): ?string
    {
        if (! $this->work_phase_id) {
            return $this->phase ?: null;
        }

        return WorkPhase::find($this->work_phase_id)?->name;
    }

    protected function selectedNetworkScopeName(): ?string
    {
        if (! $this->network_scope_id) {
            return null;
        }

        return NetworkScope::find($this->network_scope_id)?->name;
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

    protected function canManageAdministrativeFields(): bool
    {
        return auth()->check() && auth()->user()->hasAnyRole(['admin', 'supervisor']);
    }
}
