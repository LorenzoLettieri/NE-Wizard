<?php

namespace App\Http\Controllers;

use App\Models\Central;
use App\Models\Comune;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Ricerca per le select con migliaia di voci (centrali, comuni): i form non stampano più tutte le opzioni
class LookupController extends Controller
{
    public const SOURCES = [
        'centrals' => [Central::class, 'central'],
        'comuni' => [Comune::class, 'name'],
    ];

    private const MAX_RESULTS = 50;

    public function __invoke(Request $request, string $type): JsonResponse
    {
        [$modelClass, $labelColumn] = self::SOURCES[$type];

        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'ids' => ['nullable', 'array', 'max:'.self::MAX_RESULTS],
            'ids.*' => ['integer'],
        ]);

        $query = $modelClass::query()->select(['id', $labelColumn]);

        if (! empty($validated['ids'])) {
            $query->whereKey($validated['ids']);
        } else {
            $term = trim((string) ($validated['q'] ?? ''));

            if (mb_strlen($term) < 2) {
                return response()->json([]);
            }

            $query->where($labelColumn, 'like', '%'.addcslashes($term, '%_\\').'%')
                ->orderBy($labelColumn)
                ->limit(self::MAX_RESULTS);
        }

        return response()->json(
            $query->get()->map(fn ($row) => [
                'value' => (string) $row->getKey(),
                'text' => (string) $row->{$labelColumn},
            ])->values()
        );
    }
}
