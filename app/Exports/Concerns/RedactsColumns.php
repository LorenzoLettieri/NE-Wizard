<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

/**
 * Toglie dall'export le colonne che l'utente non può vedere, mantenendo allineati intestazioni,
 * valori e formati di colonna (le lettere vengono ricalcolate dopo la rimozione).
 */
trait RedactsColumns
{
    /**
     * @return list<string>
     */
    abstract protected function allHeadings(): array;

    /**
     * @return list<mixed>
     */
    abstract protected function allValues($row): array;

    /**
     * Formati indicizzati per lettera di colonna dell'export completo.
     *
     * @return array<string, string>
     */
    abstract protected function allColumnFormats(): array;

    /**
     * @return list<string>
     */
    abstract protected function hiddenHeadings(): array;

    public function headings(): array
    {
        return array_values(array_intersect_key($this->allHeadings(), $this->visibleIndexes()));
    }

    public function map($row): array
    {
        return array_values(array_intersect_key($this->allValues($row), $this->visibleIndexes()));
    }

    public function columnFormats(): array
    {
        $newPositions = array_flip(array_keys($this->visibleIndexes()));
        $formats = [];

        foreach ($this->allColumnFormats() as $letter => $format) {
            $originalIndex = Coordinate::columnIndexFromString($letter) - 1;

            if (isset($newPositions[$originalIndex])) {
                $formats[Coordinate::stringFromColumnIndex($newPositions[$originalIndex] + 1)] = $format;
            }
        }

        return $formats;
    }

    /**
     * @return array<int, true>
     */
    private function visibleIndexes(): array
    {
        $hidden = $this->hiddenHeadings();
        $visible = [];

        foreach ($this->allHeadings() as $index => $heading) {
            if (! in_array($heading, $hidden, true)) {
                $visible[$index] = true;
            }
        }

        return $visible;
    }
}
