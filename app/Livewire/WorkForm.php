<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use App\Models\CompanyWorkPhaseRate;
use App\Models\NetworkScope;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;

class WorkForm extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;

    public $operators;
    public $companies, $centrals, $workPhases, $networkScopes;

    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $network_scope_id, $description, $type, $phase, $work_phase_id, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne, $expected_delivery_date;

    public $go_live, $date_in_str, $date_out_str;

    public bool $daphne;

    public function store()
    {
        $this->validate($this->rules());

        $work = Work::create($this->workPayload());
        $work->users()->attach($this->operator_id, ['created_at' => Carbon::now()]);

        if ($this->files) {
            $this->persistUploadedFiles($work, 'works_media');
        }

        session()->flash('success', 'Lavorazione Aggiunta con successo!');

        $this->redirect(route('addWork'));
    }

    public function mount()
    {
        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->workPhases = WorkPhase::orderBy('name')->get();
        $this->networkScopes = NetworkScope::orderBy('name')->get();
        $this->operators = User::permission('get works')->get();
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
        return array_merge([
            'company_id' => $this->company_id,
            'central_id' => $this->central_id,
            'status' => $this->status,
            'network' => $this->network,
            'ao_cno' => $this->ao_cno,
            'ntw_scope' => $this->selectedNetworkScopeName(),
            'network_scope_id' => $this->network_scope_id ?: null,
            'description' => $this->description,
            'type' => $this->type,
            'phase' => $this->selectedWorkPhaseName(),
            'work_phase_id' => $this->work_phase_id ?: null,
            'daphne' => $this->daphne,
            'tempo_daphne' => $this->tempo_daphne,
            'company_assistant' => $this->company_assistant,
            'nroe' => $this->nroe,
            'wo_number' => $this->wo_number,
            'unica_number' => $this->unica_number,
            'go_live' => $this->go_live !== '' ? (int) $this->go_live : null,
            'date_in_str' => $this->date_in_str,
            'date_out_str' => $this->date_out_str,
            'notes' => $this->notes,
            'expected_delivery_date' => $this->expected_delivery_date,
        ], $this->accountingPayload());
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

    protected function selectedNetworkScopeName(): ?string
    {
        if (! $this->network_scope_id) {
            return null;
        }

        return NetworkScope::find($this->network_scope_id)?->name;
    }

    public function render()
    {
        return view('livewire.work-form');
    }
}
