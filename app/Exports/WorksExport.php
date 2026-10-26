<?php

namespace App\Exports;

use App\Models\Work;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    use Exportable;

    private const ACCOUNTING_HEADINGS = ['Importo contabilizzato', 'Tariffa unitaria'];

    protected string $dateField;
    protected Carbon $start;
    protected Carbon $end;
    protected bool $includeAccounting;

    public function __construct(string $dateField, $startDate, $endDate, bool $includeAccounting = true)
    {
        $this->dateField = $dateField;
        $this->start = Carbon::parse($startDate)->startOfDay();
        $this->end = Carbon::parse($endDate)->endOfDay();
        $this->includeAccounting = $includeAccounting;
    }

    public function query()
    {
        // includo anche central.region per la colonna "Regione"
        return Work::query()
            ->with([
                'company:id,name',
                'central:id,central,region',
                'users:id,name',
                'workSuspensions:id,work_id,started_at,ended_at',
                'workPhase:id,name',
            ])
            ->whereBetween($this->dateField, [$this->start, $this->end])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return $this->withoutHiddenColumns($this->allHeadings());
    }

    private function allHeadings(): array
    {
        return [
            'Data creazione',      // A
            'Impresa',             // B
            'Centrale',            // C
            'Regione',             // D
            'Status',              // E
            'Descrizione',         // F
            'Ambito NTW',          // G
            'Tipo',                // H
            'Fase',                // I
            'N.Roe',               // J
            'Importo contabilizzato', // K
            'Tariffa unitaria',    // L
            'Network',             // M
            'WO',                  // N
            'UNICA',               // O
            'AO/CNO',              // P
            'DAPHNE',              // Q
            'Operatori Assegnati', // R
            'Data PiC',            // S (acception_date)
            'Data Consegna',       // T (delivery_date)
            'Tempo effettivo di lavorazione', // U
            'Data FL',             // V (completion_date)
            'Data In',             // W (date_in_str)
            'Data Out',            // X (date_out_str)
            'Assistente Impresa',  // Y
            'Note',                // Z
            'Storico sospensioni', // AA
        ];
    }

    public function map($work): array
    {
        $dt = fn($d) => $d ? Carbon::parse($d)->timezone('Europe/Rome') : null; // restituisco Carbon così Excel ottiene vere date

        return $this->withoutHiddenColumns([
            $dt($work->created_at),                 // Data creazione
            optional($work->company)->name,         // Impresa
            optional($work->central)->central,      // Centrale
            optional($work->central)->region,       // Regione
            $work->status,                          // Status
            $work->description,                     // Descrizione
            $work->ntw_scope,                       // Ambito NTW
            $work->type,                            // Tipo
            $work->workPhase?->name ?? $work->phase, // Fase
            $work->nroe,                            // N.Roe
            $work->accounting_amount !== null ? (float) $work->accounting_amount : null, // Importo contabilizzato
            $work->unit_rate !== null ? (float) $work->unit_rate : null, // Tariffa unitaria
            $work->network,                         // Network
            $work->wo_number,                       // WO
            $work->unica_number,                    // UNICA
            $work->ao_cno,                          // AO/CNO
            $work->daphne ? 'SI' : 'NO',            // DAPHNE
            $work->users->pluck('name')->implode(', '), // Operatori Assegnati
            $dt($work->acception_date),             // Data PiC
            $dt($work->delivery_date),              // Data Consegna
            $work->effective_processing_label ?? '-', // Tempo effettivo di lavorazione
            $dt($work->completion_date),            // Data FL
            $work->date_in_str,                     // Data In
            $work->date_out_str,                    // Data Out
            $work->company_assistant,               // Assistente Impresa
            $work->notes,                           // Note
            $work->suspension_history,              // Storico sospensioni
        ]);
    }

    public function columnFormats(): array
    {
        // Le lettere dipendono dalle colonne effettivamente esportate
        $formats = [
            'Data creazione' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'Importo contabilizzato' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
            'Tariffa unitaria' => NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE,
            'Data PiC' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'Data Consegna' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'Data FL' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];

        $columns = [];

        foreach ($this->headings() as $index => $heading) {
            if (isset($formats[$heading])) {
                $columns[Coordinate::stringFromColumnIndex($index + 1)] = $formats[$heading];
            }
        }

        return $columns;
    }

    private function withoutHiddenColumns(array $row): array
    {
        if ($this->includeAccounting) {
            return $row;
        }

        $hiddenIndexes = array_keys(array_intersect($this->allHeadings(), self::ACCOUNTING_HEADINGS));

        return array_values(array_diff_key($row, array_flip($hiddenIndexes)));
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
