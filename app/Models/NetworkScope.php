<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkScope extends Model
{
    public const DEFAULT_NAMES = [
        'FTTH',
        'FTTH PTE',
        'FTTH PNRR',
        '5G',
        'REACTIVE',
        'INCREMENTALE',
        'DESATURAZIONE',
        'NGAN',
        'GIUNZIONE',
        'SUB-LOOP',
        'Altro',
    ];

    protected $fillable = [
        'name',
    ];

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public static function options(): array
    {
        return self::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
