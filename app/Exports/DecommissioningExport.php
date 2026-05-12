<?php

namespace App\Exports;

use App\Models\Decommissioning;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DecommissioningExport implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithStyles
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
        return Decommissioning::query()
            ->with([
                'central:id,central',
                'comune:id,name',
                'regione:id,nome',
                'progettista:id,name',
            ])
            ->whereBetween($this->dateField, [$this->start, $this->end])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'Data inserimento',
            'CLLI',
            'Centrale',
            'Comune',
            'Regione',
            'Permesso Ente',
            'FL Permesso',
            'Note Permesso',
            'Progettista',
            'Stato',
            'Data IL',
            'Data FL',
            '(1) Progettazione Esecutiva Rete FTTC',
            '(2) Prog Esecutiva ripresa Rete Rigida con ARL',
            '(3) Prog Esecutiva ripresa settore cavo 100CP su ARL esistente',
            '(4) Prog Esecutiva adeguamenti Rete Trasporto',
            '(5) Prog Voice GW su FTTCab e ARL compreso compattamento',
            '(6) Prog capparato CDX su ARL compreso compattamento',
            'Prog 1.0',
            'Prog 2.0',
            'Prog 3.0',
            'Prog 4.0',
            'Prog 5.0',
            'Prog 6.0',
            'NE 1.0',
            'NE 2.0',
            'NE 3.0',
            'NE 4.0',
            'NE 5.0',
            'NE 6.0',
            'Tot Prog',
            'Tot NE',
            'Agio',
            'Pagata Prog',
            'Pagata NE',
            'Val',
        ];
    }

    public function map($decommissioning): array
    {
        $dt = fn($value) => $value ? Carbon::parse($value)->timezone('Europe/Rome') : null;

        return [
            $dt($decommissioning->created_at),
            $decommissioning->clli,
            optional($decommissioning->central)->central,
            optional($decommissioning->comune)->name,
            optional($decommissioning->regione)->nome,
            $decommissioning->permesso_ente,
            $dt($decommissioning->fl_permesso),
            $decommissioning->note_permesso,
            optional($decommissioning->progettista)->name,
            $decommissioning->status,
            $dt($decommissioning->data_il),
            $dt($decommissioning->data_fl),
            $decommissioning->qty_1,
            $decommissioning->qty_2,
            $decommissioning->qty_3,
            $decommissioning->qty_4,
            $decommissioning->qty_5,
            $decommissioning->qty_6,
            $decommissioning->prog_amount_1,
            $decommissioning->prog_amount_2,
            $decommissioning->prog_amount_3,
            $decommissioning->prog_amount_4,
            $decommissioning->prog_amount_5,
            $decommissioning->prog_amount_6,
            $decommissioning->ne_amount_1,
            $decommissioning->ne_amount_2,
            $decommissioning->ne_amount_3,
            $decommissioning->ne_amount_4,
            $decommissioning->ne_amount_5,
            $decommissioning->ne_amount_6,
            $decommissioning->tot_prog,
            $decommissioning->tot_ne,
            $decommissioning->agio,
            $decommissioning->pagata_prog ? 'SI' : 'NO',
            $decommissioning->pagata_ne ? 'SI' : 'NO',
            $decommissioning->val ? 'Vero' : 'Falso',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'G' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'K' => NumberFormat::FORMAT_DATE_DDMMYYYY,
            'L' => NumberFormat::FORMAT_DATE_DDMMYYYY,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
