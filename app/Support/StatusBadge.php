<?php

namespace App\Support;

use App\Models\PermessoEnte;

// Le colonne Status delle tabelle sono ->html(): il valore va sempre escapato prima di finire nel badge
final class StatusBadge
{
    private const WORK_CLASSES = [
        'Sospeso' => 'text-bg-warning',
        'In Lavorazione' => 'text-bg-primary',
        'Da Lavorare' => 'text-bg-info',
        'KO' => 'text-bg-danger',
        'Consegnato' => 'text-bg-success',
        'Attesa Fine Lavori' => 'bg-warning-subtle text-white',
        'Fine Lavori' => 'pill-fine-lavori',
    ];

    private const PERMESSO_CLASSES = [
        'In Lavorazione' => 'text-bg-primary',
        'Da Lavorare' => 'text-bg-info',
        'Consegnato' => 'text-bg-warning',
        'Fine Lavori' => 'pill-fine-lavori',
    ];

    private const DECOMMISSIONING_CLASSES = [
        'In Lavorazione' => 'text-bg-primary',
        'Sospeso' => 'text-bg-warning',
        'Fine Lavori' => 'text-bg-success',
        'Annullato' => 'text-bg-danger',
    ];

    public static function work(?string $status): string
    {
        return self::render($status, self::WORK_CLASSES[$status] ?? null);
    }

    public static function permesso(?string $status): string
    {
        $status = PermessoEnte::normalizeStatus($status);

        return self::render($status, self::PERMESSO_CLASSES[$status] ?? null);
    }

    public static function decommissioning(?string $status): string
    {
        return self::render($status, self::DECOMMISSIONING_CLASSES[$status] ?? 'text-bg-secondary');
    }

    private static function render(?string $status, ?string $class): string
    {
        if ($status === null || $status === '') {
            return '';
        }

        if ($class === null) {
            return e($status);
        }

        return '<span class="badge rounded-pill '.$class.'">'.e($status).'</span>';
    }
}
