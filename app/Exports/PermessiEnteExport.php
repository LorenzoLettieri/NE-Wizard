<?php

namespace App\Exports;

use App\Exports\Concerns\RedactsColumns;
use App\Models\PermessoEnte;
use App\Models\User;
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

class PermessiEnteExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
{
    use Exportable;
    use RedactsColumns;

    private const FINANCE_HEADINGS = ['Mese Saldo', 'Al DL', 'A NE', 'Delta', 'VDC1', 'VDC2', 'VDC3', 'VDC4'];

    private const OPERATOR_DATE_HEADINGS = ['Data PiC', 'Data Consegna', 'Data Completamento'];

    protected string $dateField;
    protected Carbon $start;
    protected Carbon $end;

    public function __construct(string $dateField, $startDate, $endDate, protected User $visibleTo)
    {
        $this->dateField = $dateField;
        $this->start = Carbon::parse($startDate)->startOfDay();
        $this->end = Carbon::parse($endDate)->endOfDay();
    }

    public function query()
    {
        return PermessoEnte::query()
            ->with([
                'central:id,central,region',
                'comune:id,name',
                'regione:id,nome',
                'users:id,name',
            ])
            ->whereBetween($this->dateField, [$this->start, $this->end])
            ->when(
                ! $this->visibleTo->canViewAllPermessiEnte(),
                fn ($query) => $query->whereHas('users', fn ($users) => $users->where('users.id', $this->visibleTo->getKey()))
            )
            ->orderBy('id');
    }

    protected function hiddenHeadings(): array
    {
        return array_merge(
            $this->visibleTo->canViewPermessiEnteFinance() ? [] : self::FINANCE_HEADINGS,
            $this->visibleTo->canManagePermessiEnte() ? [] : self::OPERATOR_DATE_HEADINGS,
        );
    }

    protected function allHeadings(): array
    {
        return [
            'Data creazione',           // A
            'Network',                  // B
            'Centrale',                 // C
            'Comune',                   // D
            'Regione',                  // E
            'Status',                   // F
            'Via',                      // G
            'Descrizione',              // H
            'Consegna',                 // I
            'Progetto',                 // J
            'Apertura Chiusini',        // K
            'Num Chiusini',             // L
            'Scavo fino 100m',          // M
            'Quote Aggiuntive',         // N
            'Urgente',                  // O
            'Ordinaria',                // P
            'Fine Lavori',              // Q
            'Data FL',                  // R
            'RA',                       // S
            'Data RA',                  // T
            'Evaso dal DL',             // U
            'Mese Saldo',               // V
            'Al DL',                    // W
            'A NE',                     // X
            'Delta',                    // Y
            'VDC1',                     // Z
            'VDC2',                     // AA
            'VDC3',                     // AB
            'VDC4',                     // AC
            'Data PiC',                 // AD
            'Data Consegna',            // AE (delivery_date)
            'Data Completamento',       // AF (completion_date)
            'Operatori Assegnati',      // AG
        ];
    }

    protected function allValues($permesso): array
    {
        $dt = fn($d) => $d ? Carbon::parse($d)->timezone('Europe/Rome') : null;

        return [
            $dt($permesso->created_at),             // Data creazione
            $permesso->network,                     // Network
            optional($permesso->central)->central,  // Centrale
            optional($permesso->comune)->name,      // Comune
            optional($permesso->regione)->name,     // Regione
            $permesso->status,                      // Status
            $permesso->via,                         // Via
            $permesso->descrizione,                 // Descrizione
            $dt($permesso->consegna),               // Consegna
            $permesso->progetto,                    // Progetto
            $permesso->ap_chiusini ? 'SI' : 'NO',   // Apertura Chiusini
            $permesso->num_chiusini,                // Num Chiusini
            $permesso->scavo_fino_100m ? 'SI' : 'NO', // Scavo fino 100m
            $permesso->quote_aggiuntive,            // Quote Aggiuntive
            $permesso->urgente ? 'SI' : 'NO',       // Urgente
            $permesso->ordinaria ? 'SI' : 'NO',     // Ordinaria
            $permesso->fine_lavori ? 'SI' : 'NO',   // Fine Lavori
            $dt($permesso->data_fl),                // Data FL
            $permesso->ra ? 'SI' : 'NO',            // RA
            $dt($permesso->data_ra),                // Data RA
            $dt($permesso->evaso_dal_dl),           // Evaso dal DL
            $dt($permesso->mese_saldo),             // Mese Saldo
            $permesso->al_dl,                       // Al DL
            $permesso->a_ne,                        // A NE
            $permesso->delta,                       // Delta
            $permesso->vdc1,                        // VDC1
            $permesso->vdc2,                        // VDC2
            $permesso->vdc3,                        // VDC3
            $permesso->vdc4,                        // VDC4
            $dt($permesso->acception_date),         // Data PiC
            $dt($permesso->delivery_date),          // Data Consegna
            $dt($permesso->completion_date),        // Data Completamento
            $permesso->users->pluck('name')->implode(', '), // Operatori Assegnati
        ];
    }

    protected function allColumnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'I' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'R' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'T' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'U' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'V' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'AD' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'AE' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'AF' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
