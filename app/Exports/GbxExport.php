<?php

namespace App\Exports;

use App\Models\Gbx;
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

class GbxExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
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
        return Gbx::query()
            ->with([
                'company:id,name',
                'central:id,central,region',
            ])
            ->whereBetween($this->dateField, [$this->start, $this->end])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Data creazione',           // A
            'Impresa',                  // B
            'Centrale',                 // C
            'Comune',                   // D
            'Network',                  // E
            'SDF',                      // F
            'Cliente',                  // G
            'Coordinate',               // H
            'Data Appuntamento',        // I
            'Data Sopralluogo',         // J
            'Data Verbale',             // K
            'Adeguato',                 // L
            'Data Obbligo',             // M
            'Data Rilascio',            // N
            'Data Progetto',            // O
            'Data Speedark',            // P
            'Permessi',                 // Q
            'Data Rich. Permessi',      // R
            'Data Ott. Permessi',       // S
            'Avanzamento CO',           // T
            'Data Agg. Cartellino',     // U
            'Valore',                   // V
            'Pagato Impresa',           // W
            'Pagato Bezzi',             // X
            'Pagato Progetto',          // Y
            'Pagato DL',                // Z
            'Note Sopralluogo',         // AA
            'Note Permessi',            // AB
            'Note Progetto',            // AC
            'Note Cliente',             // AD
            'Data',                     // AE
        ];
    }

    public function map($gbx): array
    {
        $dt = fn($d) => $d ? Carbon::parse($d)->timezone('Europe/Rome') : null;

        return [
            $dt($gbx->created_at),                  // Data creazione
            optional($gbx->company)->name,          // Impresa
            optional($gbx->central)->central,       // Centrale
            $gbx->comune,                           // Comune
            $gbx->network,                          // Network
            $gbx->SDF,                              // SDF
            $gbx->client,                           // Cliente
            $gbx->coordinates,                      // Coordinate
            $dt($gbx->appointment_date),            // Data Appuntamento
            $dt($gbx->inspection_date),             // Data Sopralluogo
            $dt($gbx->verbal_date),                 // Data Verbale
            $gbx->is_adeguate ? 'SI' : 'NO',        // Adeguato
            $dt($gbx->obligation_date),             // Data Obbligo
            $dt($gbx->release_date),                // Data Rilascio
            $dt($gbx->project_date),                // Data Progetto
            $dt($gbx->speedark_date),               // Data Speedark
            $gbx->permissions ? 'SI' : 'NO',        // Permessi
            $dt($gbx->permission_request_date),     // Data Rich. Permessi
            $dt($gbx->permission_obtain_date),      // Data Ott. Permessi
            $gbx->CO_advancement,                   // Avanzamento CO
            $dt($gbx->cart_update_date),            // Data Agg. Cartellino
            $gbx->value,                            // Valore
            $gbx->company_paid ? 'SI' : 'NO',       // Pagato Impresa
            $gbx->bezzi_paid ? 'SI' : 'NO',         // Pagato Bezzi
            $gbx->project_paid ? 'SI' : 'NO',       // Pagato Progetto
            $gbx->dl_paid ? 'SI' : 'NO',            // Pagato DL
            $gbx->inspection_notes,                 // Note Sopralluogo
            $gbx->permission_notes,                 // Note Permessi
            $gbx->project_notes,                    // Note Progetto
            $gbx->client_notes,                     // Note Cliente
            $dt($gbx->date),                        // Data
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'J' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'M' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'N' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'O' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'P' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'R' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'S' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'U' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'AE' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
