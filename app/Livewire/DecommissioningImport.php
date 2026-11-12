<?php

namespace App\Livewire;

use App\Livewire\Concerns\AuthorizesComponentAccess;
use App\Models\User;
use App\Services\DecommissioningMassUpdate;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Modifica massiva dei decommissioning da Excel: caricamento, anteprima di ogni modifica, conferma.
 *
 * Il piano non resta nello stato Livewire (finirebbe nel browser con gli importi): alla conferma il file
 * viene rianalizzato e si applica solo se il risultato è identico all'anteprima mostrata.
 */
class DecommissioningImport extends Component
{
    use AuthorizesComponentAccess;
    use WithFileUploads;

    /** @var TemporaryUploadedFile|null */
    public $file;

    /** Anteprima mostrata, senza payload né valori nascosti all'utente */
    public ?array $preview = null;

    public ?string $planHash = null;

    public ?string $notice = null;

    public ?string $resultMessage = null;

    protected function canAccessComponent(User $user): bool
    {
        return $user->canImportDecommissionings();
    }

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx,xls'],
        ];
    }

    protected function messages(): array
    {
        return [
            'file.required' => 'Seleziona un file Excel.',
            'file.extensions' => 'Il file deve essere un Excel (.xlsx o .xls).',
            'file.max' => 'Il file non può superare 10 MB.',
        ];
    }

    public function updatedFile(): void
    {
        $this->resetPreview();
        $this->resultMessage = null;
        $this->validateOnly('file');
    }

    public function analyze(): void
    {
        $this->validate();
        $this->resultMessage = null;

        $this->showPlan($this->service()->analyze($this->file->getRealPath()));
    }

    public function apply(): void
    {
        $this->validate();

        if ($this->planHash === null) {
            return;
        }

        $service = $this->service();
        $plan = $service->analyze($this->file->getRealPath());

        if ($plan['fatal'] !== null || $plan['hash'] !== $this->planHash) {
            $this->showPlan($plan);
            $this->notice = 'I dati sono cambiati dopo l\'anteprima: controlla di nuovo le modifiche prima di confermare.';

            return;
        }

        $count = $service->apply($plan);

        Log::info('Import Excel decommissioning applicato', [
            'user_id' => auth()->id(),
            'file' => $this->file->getClientOriginalName(),
            'updated' => $count,
            'ids' => array_column($plan['updates'], 'id'),
        ]);

        $this->resultMessage = $count === 1
            ? '1 decommissioning aggiornato.'
            : "{$count} decommissioning aggiornati.";

        $this->resetPreview();
        $this->file = null;
    }

    public function cancel(): void
    {
        $this->resetPreview();
        $this->file = null;
        $this->resultMessage = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.decommissioning-import');
    }

    private function showPlan(array $plan): void
    {
        $this->notice = null;
        $this->planHash = $plan['fatal'] === null && $plan['updates'] !== [] ? $plan['hash'] : null;

        $plan['updates'] = array_map(
            fn (array $update) => array_diff_key($update, array_flip(['payload', 'before'])),
            $plan['updates'],
        );
        unset($plan['hash']);

        $this->preview = $plan;
    }

    private function resetPreview(): void
    {
        $this->preview = null;
        $this->planHash = null;
        $this->notice = null;
    }

    private function service(): DecommissioningMassUpdate
    {
        return new DecommissioningMassUpdate(auth()->user());
    }
}
