<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Work extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'central_id',
        'status',
        'network',
        'ao_cno',
        'description',
        'ntw_scope',
        'network_scope_id',
        'type',
        'phase',
        'work_phase_id',
        'daphne',
        'company_assistant',
        'completion_date',
        'expected_delivery_date',
        'acception_date',
        'delivery_date',
        'nroe',
        'unit_rate',
        'accounting_amount',
        'wo_number',
        'unica_number',
        'suspension_history',
        'notes',
        'go_live',
        'date_in',
        'date_out',
        'date_in_str',
        'date_out_str',
        'tempo_daphne'
    ];

    protected function casts(): array
    {
        return [
            'completion_date' => 'date',
            'expected_delivery_date' => 'date',
            'acception_date' => 'datetime',
            'delivery_date' => 'datetime',
            'go_live' => 'boolean',
            'date_in' => 'date',
            'date_out' => 'date',
            'daphne' => 'boolean',
            'unit_rate' => 'decimal:2',
            'accounting_amount' => 'decimal:2',
        ];
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function central()
    {
        return $this->belongsTo(Central::class);
    }

    public function workPhase(): BelongsTo
    {
        return $this->belongsTo(WorkPhase::class);
    }

    public function networkScope(): BelongsTo
    {
        return $this->belongsTo(NetworkScope::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    public function workSuspensions(): HasMany
    {
        return $this->hasMany(WorkSuspension::class)->orderBy('started_at');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(WorkStatusHistory::class)->orderBy('started_at');
    }

    public function getTotalSuspensionSecondsAttribute(): int
    {
        if (! $this->acception_date || ! $this->delivery_date) {
            return 0;
        }

        return $this->calculateSuspensionSecondsWithinWindow($this->acception_date, $this->delivery_date);
    }

    public function getEffectiveProcessingSecondsAttribute(): ?int
    {
        if (! $this->acception_date || ! $this->delivery_date) {
            return null;
        }

        if ($this->delivery_date->lessThanOrEqualTo($this->acception_date)) {
            return 0;
        }

        $grossSeconds = $this->acception_date->diffInSeconds($this->delivery_date);

        return max(0, $grossSeconds - $this->total_suspension_seconds);
    }

    public function getEffectiveProcessingLabelAttribute(): ?string
    {
        return self::formatDuration($this->effective_processing_seconds);
    }

    public function calculateSuspensionSecondsWithinWindow(CarbonInterface $windowStart, CarbonInterface $windowEnd): int
    {
        if ($windowEnd->lessThanOrEqualTo($windowStart)) {
            return 0;
        }

        $this->loadMissing('workSuspensions');

        return $this->workSuspensions->sum(function (WorkSuspension $suspension) use ($windowStart, $windowEnd): int {
            $suspensionEnd = $suspension->ended_at ?? $windowEnd;
            $overlapStart = $suspension->started_at->greaterThan($windowStart) ? $suspension->started_at : $windowStart;
            $overlapEnd = $suspensionEnd->lessThan($windowEnd) ? $suspensionEnd : $windowEnd;

            if ($overlapEnd->lessThanOrEqualTo($overlapStart)) {
                return 0;
            }

            return $overlapStart->diffInSeconds($overlapEnd);
        });
    }

    public function openSuspension(CarbonInterface $startedAt): WorkSuspension
    {
        $existingOpenSuspension = $this->workSuspensions()
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->lockForUpdate()
            ->first();

        if ($existingOpenSuspension) {
            return $existingOpenSuspension;
        }

        $suspension = $this->workSuspensions()->create([
            'started_at' => $startedAt,
        ]);

        $this->unsetRelation('workSuspensions');

        return $suspension;
    }

    public function closeOpenSuspension(CarbonInterface $endedAt): ?WorkSuspension
    {
        $openSuspension = $this->workSuspensions()
            ->whereNull('ended_at')
            ->orderByDesc('started_at')
            ->lockForUpdate()
            ->first();

        if (! $openSuspension) {
            return null;
        }

        $closeAt = $endedAt->lessThan($openSuspension->started_at)
            ? $openSuspension->started_at
            : $endedAt;

        $openSuspension->forceFill([
            'ended_at' => $closeAt,
        ])->save();

        $this->unsetRelation('workSuspensions');

        return $openSuspension;
    }

    public static function formatDuration(?int $seconds): ?string
    {
        if ($seconds === null) {
            return null;
        }

        $seconds = max(0, $seconds);
        $days = intdiv($seconds, 86400);
        $seconds %= 86400;
        $hours = intdiv($seconds, 3600);
        $seconds %= 3600;
        $minutes = intdiv($seconds, 60);

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days}g";
        }

        if ($hours > 0) {
            $parts[] = "{$hours}h";
        }

        if ($minutes > 0 || empty($parts)) {
            $parts[] = "{$minutes}m";
        }

        return implode(' ', $parts);
    }
}
