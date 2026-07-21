<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkPhase extends Model
{
    public const DEFAULT_NAMES = [
        'FASE 1',
        'FASE 2',
        'AGGIORNAMENTO',
        'MODIFICA',
        'CANCELLAZIONE PTE',
        'CAMBIO PTE ROE',
        'BONIFICA PTE',
        'INS SPLITTER ARLO',
        'ALTRA BONIFICA',
        'DESA ROE',
        'DESA PTE',
        'RFOS',
        'COD PRIMARIA',
        'NTW CU',
        'NTW FO',
        'NET FO 5G',
    ];

    public const LEGACY_ALIASES = [
        'STEP 1' => 'FASE 1',
        'STEP 2' => 'FASE 2',
        'AGGIORN' => 'AGGIORNAMENTO',
    ];

    protected $fillable = [
        'name',
        'score_coefficient',
    ];

    protected function casts(): array
    {
        return [
            'score_coefficient' => 'decimal:2',
        ];
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function companyRates(): HasMany
    {
        return $this->hasMany(CompanyWorkPhaseRate::class);
    }

    public static function normalizeLegacyName(?string $name): ?string
    {
        $normalized = trim((string) $name);

        if ($normalized === '') {
            return null;
        }

        return self::LEGACY_ALIASES[$normalized] ?? $normalized;
    }

    public static function options(): array
    {
        return self::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
