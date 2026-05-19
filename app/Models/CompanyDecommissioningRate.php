<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyDecommissioningRate extends Model
{
    public const ITEM_LABELS = [
        1 => '(1) Progettazione Esecutiva Rete FTTC',
        2 => '(2) Prog Esecutiva ripresa Rete Rigida con ARL',
        3 => '(3) Prog Esecutiva ripresa settore cavo 100CP su ARL esistente',
        4 => '(4) Prog Esecutiva adeguamenti Rete Trasporto',
        5 => '(5) Prog Voice GW su FTTCab e ARL compreso compattamento',
        6 => '(6) Prog capparato CDX su ARL compreso compattamento',
    ];

    protected $fillable = [
        'company_id',
        'item_index',
        'prog_price',
        'ne_price',
    ];

    protected function casts(): array
    {
        return [
            'item_index' => 'integer',
            'prog_price' => 'decimal:2',
            'ne_price' => 'decimal:2',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
