<?php

namespace App\Services;

use App\Models\Central;
use App\Models\Company;
use App\Models\CompanyDecommissioningRate;
use App\Models\Comune;
use App\Models\Decommissioning;
use App\Models\Regione;
use App\Models\User;
use App\Support\Authorization\PermissionCatalog;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;
use Throwable;

/**
 * Modifica massiva dei decommissioning da un file Excel.
 *
 * La prima riga contiene le intestazioni (le stesse dell'export) e una colonna CLLI identifica il record.
 * Si aggiornano solo le colonne riconosciute e solo le celle piene: una cella vuota lascia il valore com'è.
 * Gli importi seguono le stesse regole del form: quantità o impresa modificate ricalcolano gli importi
 * dalle tariffe (chi gestisce la contabilità può fornirli esplicitamente), e i totali seguono gli importi.
 *
 * analyze() non scrive nulla e descrive tutto ciò che succederebbe; apply() esegue quel piano.
 */
final class DecommissioningMassUpdate
{
    public const MAX_ROWS = 5000;

    private const IGNORED_HEADINGS = [
        'data inserimento' => 'Non modificabile',
        'tot prog' => 'Calcolato automaticamente dagli importi',
        'tot ne' => 'Calcolato automaticamente dagli importi',
        'agio' => 'Calcolato automaticamente dagli importi',
    ];

    private const EXCEL_ERRORS = ['#N/A', '#VALUE!', '#REF!', '#DIV/0!', '#NUM!', '#NAME?', '#NULL!', '#SPILL!', '#CALC!'];

    private const DATE_FORMATS = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'Y-m-d H:i:s', 'd/m/Y H:i', 'd/m/Y H:i:s'];

    private const TRUE_VALUES = ['SI', 'SÌ', 'SÍ', 'VERO', 'TRUE', '1'];

    private const FALSE_VALUES = ['NO', 'FALSO', 'FALSE', '0'];

    /** @var array<string, array{byName: array<string, list<int>>, names: array<int, string>}> */
    private array $lookups = [];

    /** @var Collection<int, Collection<int, CompanyDecommissioningRate>>|null */
    private ?Collection $rates = null;

    public function __construct(private readonly User $user) {}

    /**
     * Legge il file e prepara il piano, senza modificare nulla.
     *
     * @return array{
     *     fatal: ?string,
     *     rowsRead: int,
     *     columns: list<array{heading: string, label: string}>,
     *     ignoredColumns: list<array{heading: string, reason: string}>,
     *     updates: list<array{row: int, id: int, clli: string, comune: ?string, changes: list<array{label: string, old: string, new: string, derived: bool}>, hiddenChanges: bool, payload: array<string, mixed>, before: array<string, mixed>}>,
     *     unchanged: int,
     *     errors: list<array{row: int, clli: string, messages: list<string>}>,
     *     hash: ?string
     * }
     */
    public function analyze(string $path): array
    {
        $plan = [
            'fatal' => null,
            'rowsRead' => 0,
            'columns' => [],
            'ignoredColumns' => [],
            'updates' => [],
            'unchanged' => 0,
            'errors' => [],
            'hash' => null,
        ];

        try {
            $rows = $this->readRows($path);
        } catch (Throwable) {
            return ['fatal' => 'Impossibile leggere il file: verifica che sia un Excel valido (.xlsx o .xls).'] + $plan;
        }

        $headings = array_shift($rows) ?? [];
        $clliIndex = null;
        $columns = [];

        foreach ($headings as $index => $heading) {
            $heading = trim((string) $heading);
            $normalized = $this->normalizeHeading($heading);

            if ($normalized === '') {
                continue;
            }

            if ($normalized === 'clli') {
                if ($clliIndex !== null) {
                    return ['fatal' => 'La colonna CLLI compare più volte nella prima riga.'] + $plan;
                }

                $clliIndex = $index;

                continue;
            }

            $field = $this->headingAliases()[$normalized] ?? null;

            if ($field === null) {
                $plan['ignoredColumns'][] = [
                    'heading' => $heading,
                    'reason' => self::IGNORED_HEADINGS[$normalized] ?? 'Colonna non riconosciuta',
                ];

                continue;
            }

            if ($this->isFinanceField($field) && ! $this->user->canEditDecommissioningFinance()) {
                $plan['ignoredColumns'][] = ['heading' => $heading, 'reason' => 'Serve il permesso di modifica della contabilità'];

                continue;
            }

            if (in_array($field, $columns, true)) {
                $plan['ignoredColumns'][] = ['heading' => $heading, 'reason' => 'Colonna ripetuta: vale la prima'];

                continue;
            }

            $columns[$index] = $field;
            $plan['columns'][] = ['heading' => $heading, 'label' => $this->fields()[$field]['label']];
        }

        if ($clliIndex === null) {
            return ['fatal' => 'Nella prima riga manca la colonna "CLLI".'] + $plan;
        }

        if ($columns === []) {
            return ['fatal' => 'Nessuna colonna modificabile riconosciuta: usa le intestazioni dell\'export.'] + $plan;
        }

        // Numero di riga Excel => celle, saltando le righe completamente vuote
        $dataRows = [];
        foreach ($rows as $offset => $row) {
            if (collect($row)->contains(fn ($value) => ! $this->isBlank($value))) {
                $dataRows[$offset + 2] = $row;
            }
        }

        if (count($dataRows) > self::MAX_ROWS) {
            return ['fatal' => 'Il file ha '.count($dataRows).' righe: il massimo per import è '.self::MAX_ROWS.'.'] + $plan;
        }

        $plan['rowsRead'] = count($dataRows);

        $clliByRow = [];
        foreach ($dataRows as $rowNumber => $row) {
            $clliByRow[$rowNumber] = $this->normalizeKey($this->toText($row[$clliIndex] ?? null));
        }

        $rowsByClli = [];
        foreach ($clliByRow as $rowNumber => $clli) {
            if ($clli !== '') {
                $rowsByClli[$clli][] = $rowNumber;
            }
        }

        $records = $this->loadRecords(array_keys($rowsByClli));
        $this->preloadLookups(array_values($columns));

        foreach ($dataRows as $rowNumber => $row) {
            $clli = $clliByRow[$rowNumber];
            $displayClli = trim($this->toText($row[$clliIndex] ?? null));

            if ($clli === '') {
                $plan['errors'][] = ['row' => $rowNumber, 'clli' => '', 'messages' => ['CLLI mancante']];

                continue;
            }

            if (count($rowsByClli[$clli]) > 1) {
                $plan['errors'][] = [
                    'row' => $rowNumber,
                    'clli' => $displayClli,
                    'messages' => ['CLLI ripetuto nel file (righe '.implode(', ', $rowsByClli[$clli]).'): nessuna di queste righe viene applicata'],
                ];

                continue;
            }

            $matches = $records[$clli] ?? [];

            if ($matches === []) {
                $plan['errors'][] = ['row' => $rowNumber, 'clli' => $displayClli, 'messages' => ['CLLI non trovato']];

                continue;
            }

            if (count($matches) > 1) {
                $plan['errors'][] = [
                    'row' => $rowNumber,
                    'clli' => $displayClli,
                    'messages' => ['Più decommissioning hanno questo CLLI (ID '.implode(', ', array_map(fn ($record) => $record->id, $matches)).'): correggili prima dall\'applicazione'],
                ];

                continue;
            }

            $record = $matches[0];
            $values = [];
            $messages = [];

            foreach ($columns as $index => $field) {
                $raw = $row[$index] ?? null;

                if ($this->isBlank($raw)) {
                    continue;
                }

                try {
                    $values[$field] = $this->parse($field, $raw);
                } catch (RuntimeException $exception) {
                    $messages[] = $this->fields()[$field]['label'].': '.$exception->getMessage();
                }
            }

            if ($messages !== []) {
                $plan['errors'][] = ['row' => $rowNumber, 'clli' => $displayClli, 'messages' => $messages];

                continue;
            }

            $update = $this->describeUpdate($record, $values);

            if ($update === null) {
                $plan['unchanged']++;

                continue;
            }

            $plan['updates'][] = [
                'row' => $rowNumber,
                'id' => $record->id,
                'clli' => $record->clli,
                'comune' => $record->comune?->name,
            ] + $update;
        }

        $plan['hash'] = hash('sha256', json_encode(array_map(
            fn (array $update) => [$update['id'], $update['payload'], $update['before']],
            $plan['updates'],
        )));

        return $plan;
    }

    /**
     * Applica un piano prodotto da analyze(), tutto o niente.
     */
    public function apply(array $plan): int
    {
        if ($plan['fatal'] !== null) {
            throw new RuntimeException($plan['fatal']);
        }

        return DB::transaction(function () use ($plan): int {
            foreach ($plan['updates'] as $update) {
                $record = Decommissioning::query()->lockForUpdate()->findOrFail($update['id']);

                if (! $this->user->can('update', $record)) {
                    throw new RuntimeException("Non puoi modificare il decommissioning {$update['clli']}.");
                }

                $record->fill($update['payload'])->save();
            }

            return count($plan['updates']);
        });
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path, [IOFactory::READER_XLSX, IOFactory::READER_XLS]);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);

        try {
            return $spreadsheet->getSheet(0)->toArray(null, true, false, false);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Payload da salvare (valori del file + importi derivati) e modifiche da mostrare; null se non cambia nulla.
     */
    private function describeUpdate(Decommissioning $record, array $values): ?array
    {
        $payload = $values;
        $canEditFinance = $this->user->canEditDecommissioningFinance();
        $differs = fn (string $field, mixed $value) => $this->comparable($field, $record->{$field}) !== $this->comparable($field, $value);
        $changedInputs = array_keys(array_filter($values, fn ($value, $field) => $differs($field, $value), ARRAY_FILTER_USE_BOTH));

        // Come nel form: cambiare quantità o impresa ricalcola gli importi dalle tariffe dell'impresa.
        // Una colonna presente ma con lo stesso valore non deve toccare importi già sistemati a mano.
        if (array_intersect($changedInputs, ['company_id', ...$this->quantityFields()]) !== []) {
            $companyId = array_key_exists('company_id', $values) ? $values['company_id'] : $record->company_id;
            $rates = $this->ratesFor($companyId === null ? null : (int) $companyId);

            foreach (array_keys(CompanyDecommissioningRate::ITEM_LABELS) as $index) {
                $quantity = array_key_exists('qty_'.$index, $values) ? $values['qty_'.$index] : ($record->{'qty_'.$index} ?? 0);
                $rate = $rates[$index] ?? null;

                foreach (['prog_amount_' => 'prog_price', 'ne_amount_' => 'ne_price'] as $prefix => $priceField) {
                    // Un importo scritto nel file da chi gestisce la contabilità vince sul calcolo
                    if ($canEditFinance && array_key_exists($prefix.$index, $values)) {
                        continue;
                    }

                    $payload[$prefix.$index] = round((int) $quantity * (float) ($rate?->{$priceField} ?? 0), 2);
                }
            }
        }

        $changedAmounts = array_filter(
            $this->amountFields(),
            fn (string $field) => array_key_exists($field, $payload) && $differs($field, $payload[$field]),
        );

        if ($changedAmounts !== []) {
            $amount = fn (string $field) => (float) (array_key_exists($field, $payload) ? $payload[$field] : ($record->{$field} ?? 0));
            $totProg = round(array_sum(array_map($amount, $this->progAmountFields())), 2);
            $totNe = round(array_sum(array_map($amount, $this->neAmountFields())), 2);

            $payload['tot_prog'] = $totProg;
            $payload['tot_ne'] = $totNe;
            $payload['agio'] = round($totNe - $totProg, 2);
        }

        $canViewFinance = $this->user->canViewDecommissioningFinance();
        $changed = [];
        $before = [];
        $changes = [];
        $hiddenChanges = false;

        foreach ($payload as $field => $new) {
            $old = $this->comparable($field, $record->{$field});

            if ($old === $this->comparable($field, $new)) {
                continue;
            }

            $changed[$field] = $new;
            $before[$field] = $old;

            if ($this->isFinanceField($field) && ! $canViewFinance) {
                $hiddenChanges = true;

                continue;
            }

            $changes[] = [
                'label' => $this->fields()[$field]['label'],
                'old' => $this->display($field, $record->{$field}),
                'new' => $this->display($field, $new),
                'derived' => ! array_key_exists($field, $values),
            ];
        }

        if ($changed === []) {
            return null;
        }

        return [
            'changes' => $changes,
            'hiddenChanges' => $hiddenChanges,
            'payload' => $changed,
            'before' => $before,
        ];
    }

    /**
     * @param  list<string>  $cllis  già normalizzati
     * @return array<string, list<Decommissioning>>
     */
    private function loadRecords(array $cllis): array
    {
        $records = [];

        foreach (array_chunk($cllis, 500) as $chunk) {
            Decommissioning::query()
                ->with([
                    'company:id,name',
                    'central:id,central',
                    'comune:id,name',
                    'regione:id,nome',
                    'progettista:id,name',
                ])
                ->when(
                    ! $this->user->canViewAllDecommissionings(),
                    fn ($query) => $query->where('progettista_id', $this->user->getKey())
                )
                ->whereIn(DB::raw('UPPER(TRIM(clli))'), $chunk)
                ->orderBy('id')
                ->get()
                ->each(function (Decommissioning $record) use (&$records): void {
                    if ($this->user->can('update', $record)) {
                        $records[$this->normalizeKey($record->clli)][] = $record;
                    }
                });
        }

        return $records;
    }

    /**
     * @return mixed valore pronto per il modello
     */
    private function parse(string $field, mixed $raw): mixed
    {
        if (is_string($raw) && in_array(strtoupper(trim($raw)), self::EXCEL_ERRORS, true)) {
            throw new RuntimeException('la cella contiene un errore di Excel ('.trim($raw).')');
        }

        $type = $this->fields()[$field]['type'];

        return match ($type) {
            'string' => $this->parseString($raw, 255),
            'text' => $this->parseString($raw, 65000),
            'date' => $this->parseDate($raw),
            'integer' => $this->parseInteger($raw),
            'amount' => $this->parseAmount($raw),
            'bool' => $this->parseBool($raw),
            'status' => $this->parseStatus($raw),
            default => $this->parseLookup($type, $raw),
        };
    }

    private function parseString(mixed $raw, int $max): string
    {
        $value = trim($this->toText($raw));

        if (mb_strlen($value) > $max) {
            throw new RuntimeException("testo troppo lungo (massimo {$max} caratteri)");
        }

        return $value;
    }

    private function parseDate(mixed $raw): string
    {
        if ($raw instanceof DateTimeInterface) {
            return Carbon::instance($raw)->format('Y-m-d');
        }

        if (is_int($raw) || is_float($raw)) {
            if ($raw < 1 || $raw > 2958465) {
                throw new RuntimeException("data non valida ({$raw})");
            }

            $date = DateTimeImmutable::createFromMutable(ExcelDate::excelToDateTimeObject($raw));
        } else {
            $text = trim((string) $raw);
            $date = null;

            foreach (self::DATE_FORMATS as $format) {
                $candidate = DateTimeImmutable::createFromFormat('!'.$format, $text);
                $problems = DateTimeImmutable::getLastErrors();

                if ($candidate !== false && ($problems === false || ($problems['warning_count'] === 0 && $problems['error_count'] === 0))) {
                    $date = $candidate;

                    break;
                }
            }

            if ($date === null) {
                throw new RuntimeException("data non valida \"{$text}\" (usa gg/mm/aaaa)");
            }
        }

        $year = (int) $date->format('Y');
        if ($year < 1990 || $year > 2100) {
            throw new RuntimeException('data fuori intervallo ('.$date->format('d/m/Y').')');
        }

        return $date->format('Y-m-d');
    }

    private function parseInteger(mixed $raw): int
    {
        $number = $this->toNumber($raw);

        if ($number === null || floor($number) !== $number || $number < 0 || $number > 2147483647) {
            throw new RuntimeException('serve un numero intero maggiore o uguale a 0 (trovato "'.$this->toText($raw).'")');
        }

        return (int) $number;
    }

    private function parseAmount(mixed $raw): float
    {
        $number = $this->toNumber($raw);

        if ($number === null || $number < 0 || $number >= 1e10) {
            throw new RuntimeException('serve un importo maggiore o uguale a 0 (trovato "'.$this->toText($raw).'")');
        }

        return round($number, 2);
    }

    private function parseBool(mixed $raw): bool
    {
        if (is_bool($raw)) {
            return $raw;
        }

        $value = mb_strtoupper(trim($this->toText($raw)));

        if (in_array($value, self::TRUE_VALUES, true)) {
            return true;
        }

        if (in_array($value, self::FALSE_VALUES, true)) {
            return false;
        }

        throw new RuntimeException('valore "'.$this->toText($raw).'" non valido: usa SI o NO');
    }

    private function parseStatus(mixed $raw): string
    {
        $value = $this->normalizeKey($this->toText($raw));

        foreach (Decommissioning::STATUSES as $status) {
            if ($this->normalizeKey($status) === $value) {
                return $status;
            }
        }

        throw new RuntimeException('stato "'.$this->toText($raw).'" non valido (ammessi: '.implode(', ', Decommissioning::STATUSES).')');
    }

    private function parseLookup(string $type, mixed $raw): int
    {
        $text = trim($this->toText($raw));
        $ids = $this->lookups[$type]['byName'][$this->normalizeKey($text)] ?? [];

        if ($ids === []) {
            throw new RuntimeException($type === 'progettista'
                ? "\"{$text}\" non trovato tra i progettisti Deco"
                : "\"{$text}\" non trovato");
        }

        if (count($ids) > 1) {
            throw new RuntimeException("\"{$text}\" corrisponde a più voci in anagrafica");
        }

        return $ids[0];
    }

    /**
     * @param  list<string>  $fields
     */
    private function preloadLookups(array $fields): void
    {
        $sources = [
            'company' => fn () => Company::query()->get(['id', 'name'])->pluck('name', 'id'),
            'central' => fn () => Central::query()->get(['id', 'central'])->pluck('central', 'id'),
            'comune' => fn () => Comune::query()->get(['id', 'name'])->pluck('name', 'id'),
            'regione' => fn () => Regione::query()->get(['id', 'nome'])->pluck('nome', 'id'),
            // Come nel form: si assegnano solo utenti abilitati come progettisti Deco
            'progettista' => fn () => User::permission(PermissionCatalog::DECO_ASSIGNABLE)->get(['id', 'name'])->pluck('name', 'id'),
        ];

        foreach ($fields as $field) {
            $type = $this->fields()[$field]['type'];

            if (! isset($sources[$type]) || isset($this->lookups[$type])) {
                continue;
            }

            $byName = [];
            $names = $sources[$type]()->all();

            foreach ($names as $id => $name) {
                $byName[$this->normalizeKey((string) $name)][] = (int) $id;
            }

            $this->lookups[$type] = ['byName' => $byName, 'names' => $names];
        }
    }

    /**
     * @return Collection<int, CompanyDecommissioningRate>
     */
    private function ratesFor(?int $companyId): Collection
    {
        $this->rates ??= CompanyDecommissioningRate::query()->get()->groupBy('company_id')
            ->map(fn (Collection $rates) => $rates->keyBy('item_index'));

        return $companyId === null ? collect() : ($this->rates[$companyId] ?? collect());
    }

    /**
     * Valore normalizzato per capire se un campo cambia davvero.
     */
    private function comparable(string $field, mixed $value): mixed
    {
        $type = $this->fields()[$field]['type'];

        // Un importo vuoto vale 0, come nel form
        if ($type === 'amount') {
            return number_format((float) ($value ?? 0), 2, '.', '');
        }

        if ($value === null || $value === '') {
            return null;
        }

        return match ($type) {
            'integer', 'company', 'central', 'comune', 'regione', 'progettista' => (int) $value,
            'bool' => (bool) $value,
            'date' => Carbon::parse($value)->format('Y-m-d'),
            default => (string) $value,
        };
    }

    private function display(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        $type = $this->fields()[$field]['type'];

        return match ($type) {
            'amount' => '€ '.number_format((float) $value, 2, ',', '.'),
            'bool' => $value ? 'SI' : 'NO',
            'date' => Carbon::parse($value)->format('d/m/Y'),
            'company', 'central', 'comune', 'regione', 'progettista' => $this->lookupName($type, (int) $value),
            default => (string) $value,
        };
    }

    private function lookupName(string $type, int $id): string
    {
        if (isset($this->lookups[$type]['names'][$id])) {
            return (string) $this->lookups[$type]['names'][$id];
        }

        // Valore attuale di un campo non presente nel file, o progettista non più abilitato
        $name = match ($type) {
            'company' => Company::whereKey($id)->value('name'),
            'central' => Central::whereKey($id)->value('central'),
            'comune' => Comune::whereKey($id)->value('name'),
            'regione' => Regione::whereKey($id)->value('nome'),
            'progettista' => User::whereKey($id)->value('name'),
        };

        return $name ?? "#{$id}";
    }

    /**
     * Campi modificabili da Excel, con l'intestazione dell'export e il tipo di valore atteso.
     *
     * @return array<string, array{label: string, type: string}>
     */
    private function fields(): array
    {
        $fields = [
            'company_id' => ['label' => 'Impresa', 'type' => 'company'],
            'central_id' => ['label' => 'Centrale', 'type' => 'central'],
            'comune_id' => ['label' => 'Comune', 'type' => 'comune'],
            'regione_id' => ['label' => 'Regione', 'type' => 'regione'],
            'permesso_ente' => ['label' => 'Permesso Ente', 'type' => 'string'],
            'fl_permesso' => ['label' => 'FL Permesso', 'type' => 'date'],
            'note_permesso' => ['label' => 'Note Permesso', 'type' => 'text'],
            'progettista_id' => ['label' => 'Progettista', 'type' => 'progettista'],
            'status' => ['label' => 'Stato', 'type' => 'status'],
            'data_il' => ['label' => 'Data IL', 'type' => 'date'],
            'data_fl' => ['label' => 'Data FL', 'type' => 'date'],
        ];

        foreach (CompanyDecommissioningRate::ITEM_LABELS as $index => $label) {
            $fields['qty_'.$index] = ['label' => $label, 'type' => 'integer'];
        }

        foreach (array_keys(CompanyDecommissioningRate::ITEM_LABELS) as $index) {
            $fields['prog_amount_'.$index] = ['label' => "Prog {$index}.0", 'type' => 'amount'];
        }

        foreach (array_keys(CompanyDecommissioningRate::ITEM_LABELS) as $index) {
            $fields['ne_amount_'.$index] = ['label' => "NE {$index}.0", 'type' => 'amount'];
        }

        return $fields + [
            'tot_prog' => ['label' => 'Tot Prog', 'type' => 'amount'],
            'tot_ne' => ['label' => 'Tot NE', 'type' => 'amount'],
            'agio' => ['label' => 'Agio', 'type' => 'amount'],
            'pagata_prog' => ['label' => 'Pagata Prog', 'type' => 'bool'],
            'pagata_ne' => ['label' => 'Pagata NE', 'type' => 'bool'],
            'val' => ['label' => 'Val', 'type' => 'bool'],
        ];
    }

    /**
     * Intestazione normalizzata => campo. Valgono l'intestazione dell'export e il nome della colonna nel database.
     *
     * @return array<string, string>
     */
    private function headingAliases(): array
    {
        $aliases = [];

        foreach ($this->fields() as $field => $definition) {
            if (in_array($field, ['tot_prog', 'tot_ne', 'agio'], true)) {
                continue;
            }

            $aliases[$this->normalizeHeading($definition['label'])] = $field;
            $aliases[$this->normalizeHeading($field)] = $field;
        }

        return $aliases;
    }

    private function isFinanceField(string $field): bool
    {
        return in_array($field, [...$this->amountFields(), 'tot_prog', 'tot_ne', 'agio', 'pagata_prog', 'pagata_ne', 'val'], true);
    }

    /**
     * @return list<string>
     */
    private function quantityFields(): array
    {
        return array_map(fn ($index) => 'qty_'.$index, array_keys(CompanyDecommissioningRate::ITEM_LABELS));
    }

    /**
     * @return list<string>
     */
    private function progAmountFields(): array
    {
        return array_map(fn ($index) => 'prog_amount_'.$index, array_keys(CompanyDecommissioningRate::ITEM_LABELS));
    }

    /**
     * @return list<string>
     */
    private function neAmountFields(): array
    {
        return array_map(fn ($index) => 'ne_amount_'.$index, array_keys(CompanyDecommissioningRate::ITEM_LABELS));
    }

    /**
     * @return list<string>
     */
    private function amountFields(): array
    {
        return [...$this->progAmountFields(), ...$this->neAmountFields()];
    }

    private function toNumber(mixed $raw): ?float
    {
        if (is_int($raw) || is_float($raw)) {
            return (float) $raw;
        }

        if (! is_string($raw)) {
            return null;
        }

        $text = str_replace([' ', "\u{00A0}", '€'], '', trim($raw));

        // Formato italiano: 1.234,56
        if (str_contains($text, ',')) {
            $text = str_replace(',', '.', str_replace('.', '', $text));
        }

        return is_numeric($text) ? (float) $text : null;
    }

    private function toText(mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }

        if (is_float($raw) && floor($raw) === $raw && abs($raw) < 1e15) {
            return (string) (int) $raw;
        }

        if (is_bool($raw)) {
            return $raw ? 'SI' : 'NO';
        }

        return (string) $raw;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private function normalizeHeading(string $heading): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($heading)));
    }

    private function normalizeKey(?string $value): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', ' ', trim((string) $value)));
    }
}
