<?php

namespace App\Observers;

use App\Models\Work;
use App\Models\WorkStatusHistory;

class WorkObserver
{
    public function created(Work $work): void
    {
        // work_status_history.status è NOT NULL: senza stato non c'è nulla da storicizzare
        if (filled($work->status)) {
            WorkStatusHistory::create([
                'work_id' => $work->id,
                'status' => $work->status,
                'started_at' => $work->created_at ?? now(),
                'ended_at' => null,
            ]);
        }

        if ($work->status === 'In Lavorazione' && ! $work->acception_date) {
            $work->timestamps = false;
            $work->acception_date = $work->created_at ?? now();
            $work->saveQuietly();
            $work->timestamps = true;
        }
    }

    public function updating(Work $work): void
    {
        if (! $work->isDirty('status')) {
            return;
        }

        WorkStatusHistory::where('work_id', $work->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => now()]);
    }

    public function updated(Work $work): void
    {
        if (! $work->wasChanged('status') || blank($work->status)) {
            return;
        }

        WorkStatusHistory::create([
            'work_id' => $work->id,
            'status' => $work->status,
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }
}
