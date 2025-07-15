<?php

namespace Database\Seeders;

use App\Models\Central;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ClaudeSeeder extends Seeder
{
    /**
     * Nome del file JSON da importare (deve essere nella cartella storage/app/seeders)
     */
    private $jsonFileName = 'works_data.json';
    
    /**
     * Batch size per inserimenti bulk
     */
    private $batchSize = 100;
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🚀 Avvio importazione dati Works da JSON...');
        
        try {
            // Verifica che il file JSON esista
            $jsonPath = 'data/works_data.json';
            $jsonRelativePath = 'data/works_data.json';
            if (!Storage::disk('public')->exists($jsonRelativePath)) {
                $this->command->error("❌ File JSON non trovato: storage/app/{$jsonPath}");
                $this->command->info("💡 Assicurati di aver copiato il file JSON in storage/app/seeders/");
                return;
            }
            
            // Leggi e decodifica il JSON
            $jsonContent = Storage::disk('public')->get('data/works_data.json');
            $jsonData = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Errore nella decodifica JSON: " . json_last_error_msg());
            }
            
            // Valida la struttura del JSON
            $this->validateJsonStructure($jsonData);
            
            // Mostra informazioni sui dati
            $this->displayImportInfo($jsonData);
            
            // Chiedi conferma prima di procedere
            if (!$this->command->confirm('Vuoi procedere con l\'importazione?', true)) {
                $this->command->info('Importazione annullata.');
                return;
            }
            
            // Pulisci la tabella se richiesto
            if ($this->command->confirm('Vuoi svuotare la tabella works prima dell\'importazione?', false)) {
                $this->truncateTable();
            }
            
            // Importa i dati
            $this->importData($jsonData['data']);
            
            // Mostra statistiche finali
            $this->showFinalStats();
            
        } catch (Exception $e) {
            $this->command->error("❌ Errore durante l'importazione: " . $e->getMessage());
            Log::error('WorksJsonSeeder Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Valida la struttura del JSON
     */
    private function validateJsonStructure(array $jsonData): void
    {
        if (!isset($jsonData['data']) || !is_array($jsonData['data'])) {
            throw new Exception("Struttura JSON non valida: manca l'array 'data'");
        }
        
        if (empty($jsonData['data'])) {
            throw new Exception("Nessun dato trovato nel JSON");
        }
        
        // Valida il primo record per verificare la presenza dei campi necessari
        $firstRecord = $jsonData['data'][0];
        $requiredFields = ['status', 'network', 'description'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $firstRecord)) {
                $this->command->warn("⚠️  Campo '{$field}' non trovato nei dati JSON");
            }
        }
    }
    
    /**
     * Mostra informazioni sui dati da importare
     */
    private function displayImportInfo(array $jsonData): void
    {
        $metadata = $jsonData['metadata'] ?? [];
        $totalRecords = count($jsonData['data']);
        
        $this->command->info('📊 Informazioni sui dati da importare:');
        $this->command->line("   File originale: " . ($metadata['original_filename'] ?? 'N/A'));
        $this->command->line("   Data conversione: " . ($metadata['conversion_date'] ?? 'N/A'));
        $this->command->line("   Righe totali originali: " . ($metadata['total_rows'] ?? 'N/A'));
        $this->command->line("   Righe valide: " . ($metadata['valid_rows'] ?? $totalRecords));
        $this->command->line("   Record da importare: {$totalRecords}");
        $this->command->line('');
    }
    
    /**
     * Svuota la tabella works
     */
    private function truncateTable(): void
    {
        $this->command->info('🗑️  Svuotamento tabella works...');
        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('works')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('✅ Tabella svuotata con successo');
    }
    
    /**
     * Importa i dati nella tabella works
     */
    private function importData(array $data): void
    {
        $totalRecords = count($data);
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        $this->command->info("📥 Importazione di {$totalRecords} record...");
        
        // Crea la progress bar
        $progressBar = $this->command->getOutput()->createProgressBar($totalRecords);
        $progressBar->start();
        
        // Processa i dati in batch
        $batches = array_chunk($data, $this->batchSize);
        
        foreach ($batches as $batchIndex => $batch) {
            try {
                $processedBatch = [];
                
                foreach ($batch as $recordIndex => $record) {
                    try {
                        $processedRecord = $this->processRecord($record);
                        if ($processedRecord) {
                            $processedBatch[] = $processedRecord;
                            $successCount++;
                        }
                    } catch (Exception $e) {
                        $errorCount++;
                        $errors[] = [
                            'record' => $recordIndex + ($batchIndex * $this->batchSize),
                            'error' => $e->getMessage(),
                            'data' => $record
                        ];
                    }
                    
                    $progressBar->advance();
                }
                
                // Inserimento batch
                if (!empty($processedBatch)) {
                    DB::table('works')->insert($processedBatch);
                }
                
            } catch (Exception $e) {
                $errorCount += count($batch);
                $errors[] = [
                    'batch' => $batchIndex,
                    'error' => $e->getMessage()
                ];
                
                $progressBar->advance(count($batch));
            }
        }
        
        $progressBar->finish();
        $this->command->line('');
        
        // Mostra risultati
        $this->command->info("✅ Importazione completata!");
        $this->command->line("   Record importati con successo: {$successCount}");
        
        if ($errorCount > 0) {
            $this->command->warn("⚠️  Record con errori: {$errorCount}");
            $this->showErrors($errors);
        }
    }
    
    /**
     * Processa un singolo record
     */
    private function processRecord(array $record): ?array
    {


        // Pulisci e valida i dati
        $processedRecord = [
            'status' => $this->cleanString($record['status'] ?? null),
            'network' => $this->cleanString($record['network'] ?? null),
            'ao_cno' => $this->cleanString($record['ao_cno'] ?? null),
            'description' => $this->cleanString($record['description'] ?? null),
            'ntw_scope' => $this->cleanString($record['ntw_scope'] ?? null),
            'type' => $this->cleanString($record['type'] ?? null),
            'phase' => $this->cleanString($record['phase'] ?? null),
            'company_assistant' => $this->cleanString($record['company_assistant'] ?? null),
            'completion_date' => $this->parseDate($record['completion_date'] ?? null),
            'acception_date' => $this->parseDateTime($record['acception_date'] ?? null),
            'delivery_date' => $this->parseDateTime($record['delivery_date'] ?? null),
            'nroe' => $this->parseInteger($record['nroe'] ?? null),
            'wo_number' => $this->cleanString($record['wo_number'] ?? null),
            'unica_number' => $this->cleanString($record['unica_number'] ?? null),
            'suspension_history' => $this->cleanLongText($record['suspension_history'] ?? null),
            'notes' => $this->cleanLongText($record['notes'] ?? null),
            'created_at' => $this->parseDateTime($record['created_at'] ?? null) ?? now(),
            'updated_at' => $this->parseDateTime($record['updated_at'] ?? null) ?? now(),
        ];

        if ($record["central"]) {
            $processedRecord["central_id"] = Central::where("central",$record['central'])->first()->id ?? null;
        }

        if ($record["company"]) {
            $processedRecord["company_id"] = Company::firstOrCreate(["name" => $record['company']])->id ?? null;
        }
        
        // Verifica che almeno un campo significativo sia presente
        if (empty($processedRecord['network']) && empty($processedRecord['description'])) {
            return null; // Salta record vuoti
        }
        

        return $processedRecord;
    }
    
    /**
     * Pulisce una stringa
     */
    private function cleanString(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }
        
        return trim($value);
    }
    
    /**
     * Pulisce un testo lungo
     */
    private function cleanLongText(?string $value): ?string
    {
        if (empty($value) || trim($value) === '') {
            return null;
        }
        
        // Rimuovi caratteri di controllo e pulisci
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/', '', $value);
        return trim($cleaned);
    }
    
    /**
     * Parsa un intero
     */
    private function parseInteger($value): ?int
    {
        if (empty($value) || $value === '0' || $value === 0) {
            return null;
        }
        
        return is_numeric($value) ? (int)$value : null;
    }
    
    /**
     * Parsa una data
     */
    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Exception $e) {
            throw new Exception("Formato data non valido: {$value}");
        }
    }
    
    /**
     * Parsa una data/ora
     */
    private function parseDateTime(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            throw new Exception("Formato data/ora non valido: {$value}");
        }
    }
    
    /**
     * Mostra gli errori
     */
    private function showErrors(array $errors): void
    {
        if (empty($errors)) {
            return;
        }
        
        $this->command->warn('Dettagli errori:');
        
        foreach (array_slice($errors, 0, 10) as $error) {
            if (isset($error['record'])) {
                $this->command->line("   Record #{$error['record']}: {$error['error']}");
            } else {
                $this->command->line("   Batch #{$error['batch']}: {$error['error']}");
            }
        }
        
        if (count($errors) > 10) {
            $remaining = count($errors) - 10;
            $this->command->line("   ... e altri {$remaining} errori");
        }
        
        // Salva log dettagliato degli errori
        Log::warning('WorksJsonSeeder Errors', $errors);
    }
    
    /**
     * Mostra statistiche finali
     */
    private function showFinalStats(): void
    {
        $totalRecords = DB::table('works')->count();
        
        $this->command->info('📈 Statistiche finali:');
        $this->command->line("   Totale record nella tabella: {$totalRecords}");
        
        // Statistiche per stato
        $statusStats = DB::table('works')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();
            
        $this->command->line('   Distribuzione per stato:');
        foreach ($statusStats as $stat) {
            $status = $stat->status ?: 'NULL';
            $this->command->line("     {$status}: {$stat->count}");
        }
        
        // Statistiche per tipo
        $typeStats = DB::table('works')
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();
            
        $this->command->line('   Distribuzione per tipo:');
        foreach ($typeStats as $stat) {
            $type = $stat->type ?: 'NULL';
            $this->command->line("     {$type}: {$stat->count}");
        }
    }
}