<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Company;
use App\Models\NetworkScope;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesMediaUploads;
use App\Livewire\Concerns\HandlesWorkFields;

class WorkForm extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;
    use HandlesWorkFields;

    public $operators;
    public $companies, $workPhases, $networkScopes;

    public $company_id, $central_id, $network, $ao_cno, $ntw_scope, $network_scope_id, $description, $type, $phase, $work_phase_id, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne, $expected_delivery_date;

    public $operator_id = [];

    public $status = 'Da Lavorare';

    public $go_live, $date_in_str, $date_out_str;

    // La colonna works.daphne non accetta null: si parte da NO
    public $daphne = '0';

    public function store()
    {
        $this->normalizeOperatorIdsInput();
        $this->validate($this->rules());

        $work = Work::create($this->workPayload());
        $work->users()->attach($this->selectedOperatorIds(), ['created_at' => Carbon::now()]);

        if ($this->files) {
            $this->persistUploadedFiles($work, 'works_media');
        }

        $this->claimCompletedUploadSessions($work);

        session()->flash('success', 'Lavorazione Aggiunta con successo!');

        $this->redirect(route('addWork'));
    }

    public function mount()
    {
        $this->companies = Company::all();
        $this->workPhases = WorkPhase::orderBy('name')->get();
        $this->networkScopes = NetworkScope::orderBy('name')->get();
        $this->operators = User::permission('get works')->get();
        $this->initializeChunkedMediaUploads('work');
    }

    protected function rules(): array
    {
        return array_merge($this->mediaUploadValidationRules(), $this->workFieldRules(), [
            'status' => $this->statusRules(creating: true),
        ]);
    }

    protected function selectedOperatorIds(): array
    {
        return collect($this->operator_id)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    protected function workPayload(): array
    {
        return array_merge([
            'company_id' => $this->blankToNull($this->company_id),
            'central_id' => $this->blankToNull($this->central_id),
            'status' => $this->status,
            'network' => $this->network,
            'ao_cno' => $this->ao_cno,
            'ntw_scope' => $this->selectedNetworkScopeName(),
            'network_scope_id' => $this->network_scope_id ?: null,
            'description' => $this->description,
            'type' => $this->type,
            'phase' => $this->selectedWorkPhaseName(),
            'work_phase_id' => $this->work_phase_id ?: null,
            'daphne' => $this->daphneValue(),
            'tempo_daphne' => $this->tempo_daphne,
            'company_assistant' => $this->company_assistant,
            'nroe' => $this->blankToNull($this->nroe),
            'wo_number' => $this->wo_number,
            'unica_number' => $this->unica_number,
            'go_live' => $this->goLiveValue(),
            'date_in_str' => $this->date_in_str,
            'date_out_str' => $this->date_out_str,
            'notes' => $this->notes,
            'expected_delivery_date' => $this->blankToNull($this->expected_delivery_date),
        ], $this->accountingPayload());
    }

    public function render()
    {
        return view('livewire.work-form', [
            'statusOptions' => $this->statusOptions(creating: true),
        ]);
    }
}
