<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timesheet extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'entry_time',
        'exit_time',
        'break_start',
        'break_end',
        'leave_start_time',
        'overtime_hours',
        'leave_hours',
        'leave_type',
    ];

    protected $casts = [
        'date' => 'date',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'leave_start_time' => 'datetime',
        'overtime_hours' => 'decimal:2',
        'leave_hours' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasClosedBreak(): bool
    {
        return $this->break_start !== null && $this->break_end !== null;
    }

    public function isHourlyLeave(): bool
    {
        return in_array($this->leave_type, ['permesso', 'malattia'], true)
            && (float) $this->leave_hours > 0;
    }

    public function isLegacyHourlyLeaveOnly(): bool
    {
        return $this->isHourlyLeave()
            && $this->leave_start_time === null
            && $this->entry_time !== null
            && $this->exit_time === null
            && $this->break_start === null
            && $this->break_end === null;
    }

    public function effectiveShiftEntryTime()
    {
        return $this->isLegacyHourlyLeaveOnly() ? null : $this->entry_time;
    }

    public function effectiveLeaveStartTime()
    {
        return $this->leave_start_time ?: ($this->isLegacyHourlyLeaveOnly() ? $this->entry_time : null);
    }
}
