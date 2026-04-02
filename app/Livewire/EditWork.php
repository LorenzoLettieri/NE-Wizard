<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use Illuminate\Support\Carbon;
use App\Livewire\Concerns\HandlesMediaUploads;
use App\Livewire\Concerns\HandlesWorkSuspensions;

class EditWork extends Component
{
    use WithFileUploads;
    use HandlesMediaUploads;
    use HandlesWorkSuspensions;

    public $work;
    public $operators;

    public $companies;
    public $centrals;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number, $unica_number, $notes, $tempo_daphne;
    public $go_live, $date_in_str, $date_out_str;
    public $daphne;

    #[On('edit-work')]
    public function editWork($id)
    {
        $this->work = Work::with('media')->find($id);

        $this->company_id = $this->work->company_id;
        $this->central_id = $this->work->central_id;
        $this->operator_id = $this->work->users->pluck('id')->toArray();
        $this->status = $this->work->status;
        $this->network = $this->work->network;
        $this->ao_cno = $this->work->ao_cno;
        $this->ntw_scope = $this->work->ntw_scope;
        $this->description = $this->work->description;
        $this->type = $this->work->type;
        $this->phase = $this->work->phase;
        $this->company_assistant = $this->work->company_assistant;
        $this->nroe = $this->work->nroe;
        $this->wo_number = $this->work->wo_number;
        $this->unica_number = $this->work->unica_number;
        $this->notes = $this->work->notes;
        $this->daphne = $this->work->daphne;
        $this->tempo_daphne = $this->work->tempo_daphne;
        $this->go_live = $this->work->go_live;
        $this->date_in_str = $this->work->date_in_str;
        $this->date_out_str = $this->work->date_out_str;

        $this->suspension_history = $this->work->suspension_history;
        $this->loadStructuredSuspensions($this->work);
        $this->files = [];
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();
    }

    public function update()
    {
        $this->validate($this->mediaUploadValidationRules());
        $validatedSuspensions = $this->validateStructuredSuspensions($this->work);

        DB::transaction(function () use ($validatedSuspensions): void {
            $this->work->update($this->except([
                'work',
                'operators',
                'companies',
                'centrals',
                'files',
                'uploadMessage',
                'uploadMessageType',
                'suspensions',
            ]));
            $this->syncOperatorsPreservingAssignmentDates();
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
        $this->dispatch('workUpdated');
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

    public function mount()
    {
        $this->companies = Company::all();
        $this->centrals = Central::all();
        $this->operators = User::permission('get works')->get();
    }

    public function render()
    {
        return view('livewire.edit-work');
    }
}
