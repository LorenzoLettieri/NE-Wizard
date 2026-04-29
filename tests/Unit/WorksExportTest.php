<?php

namespace Tests\Unit;

use App\Exports\WorksExport;
use App\Models\Central;
use App\Models\Company;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkPhase;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

class WorksExportTest extends TestCase
{
    public function test_works_export_includes_accounting_amount_and_unit_rate_columns(): void
    {
        $work = new Work([
            'created_at' => Carbon::parse('2026-03-31 07:30:00', 'UTC'),
            'status' => 'Consegnato',
            'description' => 'Attivazione',
            'ntw_scope' => 'Backbone',
            'type' => 'Delivery',
            'nroe' => 4,
            'accounting_amount' => 50.00,
            'unit_rate' => 12.50,
            'network' => 'NTW-001',
            'wo_number' => 'WO-123',
            'unica_number' => 'UNICA-456',
            'ao_cno' => 'AO-01',
            'daphne' => true,
            'acception_date' => Carbon::parse('2026-03-31 08:00:00', 'UTC'),
            'delivery_date' => Carbon::parse('2026-03-31 14:00:00', 'UTC'),
            'completion_date' => Carbon::parse('2026-03-31 15:00:00', 'UTC'),
            'date_in_str' => 'IN-1',
            'date_out_str' => 'OUT-1',
            'company_assistant' => 'Giulia',
            'notes' => 'Nota export',
            'suspension_history' => 'Storico',
        ]);

        $work->setRelation('company', new Company(['name' => 'Acme']));
        $work->setRelation('central', new Central(['central' => 'Roma 1', 'region' => 'Lazio']));
        $work->setRelation('users', new Collection([new User(['name' => 'Mario Rossi'])]));
        $work->setRelation('workPhase', new WorkPhase(['name' => 'Collaudo']));
        $work->setRelation('workSuspensions', new Collection());

        $export = new WorksExport('created_at', '2026-03-31', '2026-03-31');
        $headings = $export->headings();
        $mapped = $export->map($work);

        $this->assertSame('Importo contabilizzato', $headings[10]);
        $this->assertSame('Tariffa unitaria', $headings[11]);
        $this->assertSame(50.0, $mapped[10]);
        $this->assertSame(12.5, $mapped[11]);
        $this->assertSame('6h', $mapped[20]);
    }
}
