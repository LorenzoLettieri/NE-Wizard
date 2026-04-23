<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyWorkPhaseRate extends Model
{
    protected $fillable = [
        'company_id',
        'work_phase_id',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workPhase(): BelongsTo
    {
        return $this->belongsTo(WorkPhase::class);
    }
}
