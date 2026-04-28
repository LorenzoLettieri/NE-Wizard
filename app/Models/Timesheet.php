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
}
