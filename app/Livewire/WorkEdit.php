<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Work;
use App\Models\Central;
use App\Models\Company;
use Livewire\Component;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\WithFileUploads;
use App\Livewire\Concerns\HandlesPdfUploads;
use App\Livewire\Concerns\HandlesWorkSuspensions;

class WorkEdit extends Component
{
    use WithFileUploads;
    use HandlesPdfUploads;
    use HandlesWorkSuspensions;

    public $work;
    public $operators;
    public $companies, $centrals;

    public $suspension_history;
    public $company_id, $central_id, $operator_id, $status, $network, $ao_cno, $ntw_scope, $description, $type, $phase, $company_assistant, $nroe, $wo_number,$unica_number, $notes;

    public function update(){
        $this->validate($this->pdfUploadValidationRules());
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
        $this->operators = User::permission('get works')->get();

        $this->company_id = $work->company_id;
        $this->central_id = $work->central_id;
        $this->operator_id = $work->users->pluck('id')->toArray();
        $this->status = $work->status;
        $this->network = $work->network;
        $this->ao_cno = $work->ao_cno;
        $this->ntw_scope = $work->ntw_scope;
        $this->description = $work->description;
        $this->type = $work->type;
        $this->phase = $work->phase;
        $this->company_assistant = $work->company_assistant;
        $this->nroe = $work->nroe;
        $this->wo_number = $work->wo_number;
        $this->unica_number = $work->unica_number;
        $this->notes = $work->notes;

        $this->suspension_history = $work->suspension_history;
        $this->loadStructuredSuspensions($this->work);
        $this->files = [];
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();

    }
    public function render()
    {
        return view('livewire.work-edit');
    }
}
