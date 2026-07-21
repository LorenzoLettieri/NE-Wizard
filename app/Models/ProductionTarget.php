<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionTarget extends Model
{
    public const DEFAULT_AMOUNT_TARGET = 3500.0;
    public const DEFAULT_SCORE_TARGET = 0.0;

    protected $fillable = [
        'monthly_amount_target',
        'daily_score_target',
    ];

    protected function casts(): array
    {
        return [
            'monthly_amount_target' => 'decimal:2',
            'daily_score_target' => 'decimal:2',
        ];
    }

    /**
     * The single, global production-target configuration row.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'monthly_amount_target' => self::DEFAULT_AMOUNT_TARGET,
            'daily_score_target' => self::DEFAULT_SCORE_TARGET,
        ]);
    }
}
