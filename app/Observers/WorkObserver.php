<?php

namespace App\Observers;

use App\Models\Work;
use App\Models\WorkStatusHistory;

class WorkObserver
{
    public function created(Work $work): void
    {
        WorkStatusHistory::create([
            'work_id' => $work->id,
            'status' => $work->status,
            'started_at' => $work->created_at ?? now(),
            'ended_at' => null,
        ]);
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
        if (! $work->wasChanged('status')) {
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
