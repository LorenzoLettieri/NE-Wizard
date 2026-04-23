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
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WorksExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    use Exportable;

    protected string $dateField;
    protected Carbon $start;
    protected Carbon $end;

    public function __construct(string $dateField, $startDate, $endDate)
    {
        $this->dateField = $dateField;
        $this->start = Carbon::parse($startDate)->startOfDay();
        $this->end = Carbon::parse($endDate)->endOfDay();
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
            'Network',             // K
            'WO',                  // L
            'UNICA',               // M
            'AO/CNO',              // N
            'DAPHNE',              // O
            'Operatori Assegnati', // P
            'Data PiC',            // Q (acception_date)
            'Data Consegna',       // R (delivery_date)
            'Tempo effettivo di lavorazione', // S
            'Data FL',             // T (completion_date)
            'Data In',             // U (date_in_str)
            'Data Out',            // V (date_out_str)
            'Assistente Impresa',  // W
            'Note',                // X
            'Storico sospensioni', // Y
        ];
    }

    public function map($work): array
    {
        $dt = fn($d) => $d ? Carbon::parse($d)->timezone('Europe/Rome') : null; // restituisco Carbon così Excel ottiene vere date

        return [
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
        ];
    }

    public function columnFormats(): array
    {
        // Colonne date: A (creazione), Q (PiC), R (Consegna), T (FL)
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'Q' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'R' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'T' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
