<?php

namespace Tests\Support;

use App\Livewire\Concerns\HandlesMediaUploads;
use App\Models\Work;
use Livewire\Component;
use Livewire\WithFileUploads;

class HandlesMediaUploadsTestComponent extends Component
{
    use HandlesMediaUploads;
    use WithFileUploads;

    public Work $work;

    public function mount(Work $work): void
    {
        $this->work = $work;
    }

    public function persist(): void
    {
        if ($this->files) {
            $this->persistUploadedFiles($this->work, 'works_media');
        }
    }

    public function render()
    {
        return <<<'HTML'
        <div></div>
        HTML;
    }
}
