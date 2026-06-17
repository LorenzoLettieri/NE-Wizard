<?php

namespace App\Livewire;

use App\Livewire\Concerns\HandlesMediaUploads;
use App\Models\Central;
use App\Models\Company;
use App\Models\CompanyDecommissioningRate;
use App\Models\Comune;
use App\Models\Decommissioning;
use App\Models\Regione;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DecommissioningForm extends Component
{
    use HandlesMediaUploads;
    use WithFileUploads;

    public $central_id;
    public $company_id;
    public $clli;
    public $comune_id;
    public $regione_id;
    public $permesso_ente;
    public $fl_permesso;
    public $note_permesso;
    public $progettista_id;
    public $status = 'Da Lavorare';
    public $data_il;
    public $data_fl;
    public $qty_1;
    public $qty_2;
    public $qty_3;
    public $qty_4;
    public $qty_5;
    public $qty_6;
    public $prog_amount_1 = 0;
    public $prog_amount_2 = 0;
    public $prog_amount_3 = 0;
    public $prog_amount_4 = 0;
    public $prog_amount_5 = 0;
    public $prog_amount_6 = 0;
    public $ne_amount_1 = 0;
    public $ne_amount_2 = 0;
    public $ne_amount_3 = 0;
    public $ne_amount_4 = 0;
    public $ne_amount_5 = 0;
    public $ne_amount_6 = 0;
    public $tot_prog = 0;
    public $tot_ne = 0;
    public $agio = 0;
    public $pagata_prog = '0';
    public $pagata_ne = '0';
    public $val = '0';

    public $isEdit = false;
    public $isShow = false;
    public $decommissioningId = null;
    public $existingMedia = [];

    #[Computed]
    public function centrali()
    {
        return cache()->remember('deco_centrali_list', 3600, function () {
            return Central::orderBy('central')->pluck('central', 'id')->toArray();
        });
    }

    #[Computed]
    public function companies()
    {
        return cache()->remember('deco_companies_list', 3600, function () {
            return Company::orderBy('id')->pluck('name', 'id')->toArray();
        });
    }

    #[Computed]
    public function comuni()
    {
        return cache()->remember('deco_comuni_list', 3600, function () {
            return Comune::orderBy('name')->pluck('name', 'id')->toArray();
        });
    }

    #[Computed]
    public function regioni()
    {
        return cache()->remember('deco_regioni_list', 3600, function () {
            return Regione::orderBy('nome')->pluck('nome', 'id')->toArray();
        });
    }

    #[Computed]
    public function progettisti()
    {
        return User::role('Deco')->orderBy('name')->pluck('name', 'id')->toArray();
    }

    #[Computed]
    public function quantityLabels()
    {
        return CompanyDecommissioningRate::ITEM_LABELS;
    }

    #[On('view-decommissioning')]
    public function viewDecommissioning($id): void
    {
        $this->isShow = true;
        $this->isEdit = false;
        $this->loadDecommissioning($id);
    }

    #[On('edit-decommissioning')]
    public function editDecommissioning($id): void
    {
        $this->isShow = false;
        $this->isEdit = true;
        $this->loadDecommissioning($id);
    }

    public function mount(?Decommissioning $decommissioning = null): void
    {
        if ($decommissioning && $decommissioning->exists) {
            $this->loadDecommissioning($decommissioning->id);
            $this->isEdit = true;

            return;
        }

        if (auth()->check() && auth()->user()->hasRole('admin')) {
            $this->syncEconomicFieldsWithQuantities();
        }

        $this->initializeChunkedMediaUploads('decommissioning');
    }

    public function updated($property): void
    {
        if (
            auth()->check() &&
            auth()->user()->hasRole('admin') &&
            in_array($property, array_merge(['company_id'], $this->quantityFields()), true)
        ) {
            $this->syncEconomicFieldsWithQuantities();
        }
    }

    protected function rules(): array
    {
        return [
            'company_id' => ['nullable', Rule::exists('companies', 'id')],
            'central_id' => ['nullable', Rule::exists('centrals', 'id')],
            'clli' => 'nullable|string|max:255',
            'comune_id' => ['nullable', Rule::exists('comuni', 'id')],
            'regione_id' => ['nullable', Rule::exists('regioni', 'id')],
            'progettista_id' => ['nullable', Rule::exists('users', 'id')],
            'permesso_ente' => 'nullable|string|max:255',
            'fl_permesso' => 'nullable|date',
            'note_permesso' => 'nullable|string',
            'status' => ['required', Rule::in(['Da Lavorare', 'In Lavorazione', 'Sospeso', 'Fine Lavori', 'Annullato'])],
            'data_il' => 'nullable|date',
            'data_fl' => 'nullable|date',
            'qty_1' => 'nullable|integer|min:0',
            'qty_2' => 'nullable|integer|min:0',
            'qty_3' => 'nullable|integer|min:0',
            'qty_4' => 'nullable|integer|min:0',
            'qty_5' => 'nullable|integer|min:0',
            'qty_6' => 'nullable|integer|min:0',
            'prog_amount_1' => 'nullable|numeric|min:0',
            'prog_amount_2' => 'nullable|numeric|min:0',
            'prog_amount_3' => 'nullable|numeric|min:0',
            'prog_amount_4' => 'nullable|numeric|min:0',
            'prog_amount_5' => 'nullable|numeric|min:0',
            'prog_amount_6' => 'nullable|numeric|min:0',
            'ne_amount_1' => 'nullable|numeric|min:0',
            'ne_amount_2' => 'nullable|numeric|min:0',
            'ne_amount_3' => 'nullable|numeric|min:0',
            'ne_amount_4' => 'nullable|numeric|min:0',
            'ne_amount_5' => 'nullable|numeric|min:0',
            'ne_amount_6' => 'nullable|numeric|min:0',
            'pagata_prog' => 'nullable|in:0,1',
            'pagata_ne' => 'nullable|in:0,1',
            'val' => 'nullable|in:0,1',
        ] + $this->mediaUploadValidationRules();
    }

    public function save()
    {
        $this->validate();

        if ($this->progettista_id && ! array_key_exists((int) $this->progettista_id, $this->progettisti())) {
            $this->addError('progettista_id', 'Il progettista selezionato non ha il ruolo Deco.');

            return null;
        }

        $decommissioning = $this->isEdit
            ? Decommissioning::findOrFail($this->decommissioningId)
            : new Decommissioning();

        $data = [
            'company_id' => $this->emptyToNull($this->company_id),
            'clli' => $this->emptyToNull($this->clli),
            'central_id' => $this->emptyToNull($this->central_id),
            'comune_id' => $this->emptyToNull($this->comune_id),
            'regione_id' => $this->emptyToNull($this->regione_id),
            'progettista_id' => $this->emptyToNull($this->progettista_id),
            'permesso_ente' => $this->emptyToNull($this->permesso_ente),
            'fl_permesso' => $this->emptyToNull($this->fl_permesso),
            'note_permesso' => $this->emptyToNull($this->note_permesso),
            'status' => $this->status ?: 'Da Lavorare',
            'data_il' => $this->emptyToNull($this->data_il),
            'data_fl' => $this->emptyToNull($this->data_fl),
        ];

        if (auth()->user()->hasRole('admin')) {
            $data['val'] = $this->stringToBool($this->val);
        }

        foreach ($this->quantityFields() as $field) {
            $data[$field] = $this->normalizeIntegerField($this->$field);
        }

        if (auth()->user()->hasRole('admin')) {
            $data = array_merge($data, $this->economicPayloadFromAdminInputs());
        } else {
            $computed = $this->calculateEconomicFieldsFromQuantities();
            $data = array_merge($data, $computed, [
                'pagata_prog' => $this->isEdit ? (bool) $decommissioning->pagata_prog : false,
                'pagata_ne' => $this->isEdit ? (bool) $decommissioning->pagata_ne : false,
            ]);
        }

        $decommissioning->fill($data)->save();

        if (! $this->isEdit) {
            $this->decommissioningId = $decommissioning->id;
        }

        $uploadedCount = 0;
        if ($this->files) {
            $uploadedCount = $this->persistUploadedFiles($decommissioning, 'decommissioning_media');
        }

        $uploadedCount += $this->claimCompletedUploadSessions($decommissioning);

        $removedCount = $this->commitPendingMediaRemovals($decommissioning);

        $message = $this->isEdit ? 'Decommissioning aggiornato con successo!' : 'Decommissioning creato con successo!';

        if ($this->isEdit && ($uploadedCount > 0 || $removedCount > 0)) {
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

        return redirect()->route('decommissionings.table');
    }

    public function render()
    {
        return view('livewire.decommissioning-form');
    }

    private function loadDecommissioning(int $id): void
    {
        $decommissioning = Decommissioning::with('media')->findOrFail($id);

        $this->decommissioningId = $decommissioning->id;
        $this->company_id = $decommissioning->company_id;
        $this->clli = $decommissioning->clli;
        $this->central_id = $decommissioning->central_id;
        $this->comune_id = $decommissioning->comune_id;
        $this->regione_id = $decommissioning->regione_id;
        $this->progettista_id = $decommissioning->progettista_id;
        $this->permesso_ente = $decommissioning->permesso_ente;
        $this->fl_permesso = $decommissioning->fl_permesso ? Carbon::parse($decommissioning->fl_permesso)->format('Y-m-d') : null;
        $this->note_permesso = $decommissioning->note_permesso;
        $this->status = $decommissioning->status ?: 'Da Lavorare';
        $this->data_il = $decommissioning->data_il ? Carbon::parse($decommissioning->data_il)->format('Y-m-d') : null;
        $this->data_fl = $decommissioning->data_fl ? Carbon::parse($decommissioning->data_fl)->format('Y-m-d') : null;

        foreach ($this->quantityFields() as $field) {
            $this->$field = $decommissioning->$field;
        }

        foreach ($this->progAmountFields() as $field) {
            $this->$field = $decommissioning->$field ?? 0;
        }

        foreach ($this->neAmountFields() as $field) {
            $this->$field = $decommissioning->$field ?? 0;
        }

        $this->tot_prog = $decommissioning->tot_prog ?? 0;
        $this->tot_ne = $decommissioning->tot_ne ?? 0;
        $this->agio = $decommissioning->agio ?? 0;
        $this->pagata_prog = $this->boolToString($decommissioning->pagata_prog);
        $this->pagata_ne = $this->boolToString($decommissioning->pagata_ne);
        $this->val = $this->boolToString($decommissioning->val);

        $this->refreshExistingMedia();
        $this->files = [];
        $this->initializeChunkedMediaUploads('decommissioning', $decommissioning->id);
        $this->clearUploadFeedback();
        $this->clearPendingMediaRemovals();
    }

    private function refreshExistingMedia(): void
    {
        if (! $this->decommissioningId) {
            $this->existingMedia = [];

            return;
        }

        $this->existingMedia = Decommissioning::findOrFail($this->decommissioningId)
            ->media()
            ->latest()
            ->get(['id', 'file_name', 'file_path'])
            ->map(fn ($media) => [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'file_path' => $media->file_path,
            ])
            ->all();
    }

    private function calculateEconomicFieldsFromQuantities(): array
    {
        $data = [];
        $totProg = 0;
        $totNe = 0;

        $rates = $this->decommissioningRatesByItem();

        foreach (CompanyDecommissioningRate::ITEM_LABELS as $index => $label) {
            $quantity = $this->normalizeIntegerField($this->{'qty_' . $index}) ?? 0;
            $rate = $rates[$index] ?? null;
            $progAmount = round($quantity * (float) ($rate?->prog_price ?? 0), 2);
            $neAmount = round($quantity * (float) ($rate?->ne_price ?? 0), 2);

            $data['prog_amount_' . $index] = $progAmount;
            $data['ne_amount_' . $index] = $neAmount;

            $totProg += $progAmount;
            $totNe += $neAmount;
        }

        $data['tot_prog'] = round($totProg, 2);
        $data['tot_ne'] = round($totNe, 2);
        $data['agio'] = round($totNe - $totProg, 2);

        return $data;
    }

    private function decommissioningRatesByItem()
    {
        if (! $this->company_id) {
            return collect();
        }

        return CompanyDecommissioningRate::query()
            ->where('company_id', $this->company_id)
            ->get()
            ->keyBy('item_index');
    }

    private function syncEconomicFieldsWithQuantities(): void
    {
        $computed = $this->calculateEconomicFieldsFromQuantities();

        foreach ($computed as $field => $value) {
            $this->$field = $value;
        }
    }

    private function economicPayloadFromAdminInputs(): array
    {
        $payload = [];
        $totProg = 0;
        $totNe = 0;

        foreach ($this->progAmountFields() as $field) {
            $value = round((float) ($this->$field ?: 0), 2);
            $payload[$field] = $value;
            $totProg += $value;
        }

        foreach ($this->neAmountFields() as $field) {
            $value = round((float) ($this->$field ?: 0), 2);
            $payload[$field] = $value;
            $totNe += $value;
        }

        $payload['tot_prog'] = round($totProg, 2);
        $payload['tot_ne'] = round($totNe, 2);
        $payload['agio'] = round($totNe - $totProg, 2);
        $payload['pagata_prog'] = $this->stringToBool($this->pagata_prog);
        $payload['pagata_ne'] = $this->stringToBool($this->pagata_ne);

        return $payload;
    }

    private function quantityFields(): array
    {
        return ['qty_1', 'qty_2', 'qty_3', 'qty_4', 'qty_5', 'qty_6'];
    }

    private function progAmountFields(): array
    {
        return ['prog_amount_1', 'prog_amount_2', 'prog_amount_3', 'prog_amount_4', 'prog_amount_5', 'prog_amount_6'];
    }

    private function neAmountFields(): array
    {
        return ['ne_amount_1', 'ne_amount_2', 'ne_amount_3', 'ne_amount_4', 'ne_amount_5', 'ne_amount_6'];
    }

    private function emptyToNull($value)
    {
        return $value === '' ? null : $value;
    }

    private function normalizeIntegerField($value): ?int
    {
        return $value === '' || $value === null ? null : (int) $value;
    }

    private function stringToBool($value): bool
    {
        return (string) $value === '1';
    }

    private function boolToString($value): string
    {
        return $value ? '1' : '0';
    }
}
