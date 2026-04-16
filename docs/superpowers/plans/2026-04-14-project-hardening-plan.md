# NE-Wizard Hardening And Table Optimization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** mettere in sicurezza i flussi principali, riallineare le entita' applicative e rendere le tabelle piu' coerenti e reattive per un utilizzo desktop intensivo.

**Architecture:** l'intervento parte dal perimetro di accesso server-side e scende verso i componenti Livewire che modificano i record. Dopo il blocco delle superfici piu' rischiose, il piano normalizza i valori di dominio condivisi tra form, tabelle ed export, quindi riduce query inutili e completa la copertura test per evitare regressioni.

**Tech Stack:** Laravel 12, Livewire 3, Spatie Permission, Rappasoft Livewire Tables, Maatwebsite Excel, PHPUnit 11.

---

## File Structure

- Modify: `routes/web.php`
  responsabilita': chiudere tutte le route sensibili dietro `auth` + middleware di ruolo/permesso coerenti.
- Modify: `app/Http/Controllers/WorkController.php`
  responsabilita': validare e autorizzare export lavori.
- Modify: `app/Http/Controllers/GbxController.php`
  responsabilita': whitelist rigorosa dei campi export GBX.
- Modify: `app/Http/Controllers/PermessoEnteController.php`
  responsabilita': whitelist rigorosa dei campi export Permessi Ente.
- Modify: `app/Http/Controllers/UsersController.php`
  responsabilita': correggere hashing password e unicita' email in update.
- Modify: `app/Livewire/OperatorTable.php`
  responsabilita': enforcement ownership/authorization sulle action operatore.
- Modify: `app/Livewire/EditWork.php`
  responsabilita': autorizzazione lato server sull'editing di lavori da modal Livewire.
- Modify: `app/Livewire/ViewWork.php`
  responsabilita': autorizzazione su duplicazione e chiusura lavori.
- Modify: `app/Livewire/WorksTable.php`
  responsabilita': query piu' leggere, filtri coerenti e rimozione di chiamate superflue.
- Modify: `app/Livewire/GbxTable.php`
  responsabilita': correzione check ruoli e filtro company sicuro.
- Modify: `app/Livewire/PermessiEnteTable.php`
  responsabilita': allineare enforcement e testabilita' dei workflow di stato.
- Modify: `app/Livewire/Concerns/HandlesMediaUploads.php`
  responsabilita':naming sicuro, storage privato.
- Modify: `app/Models/Work.php`
  responsabilita': centralizzare enum/valori dominio condivisi.
- Modify: `app/Models/PermessoEnte.php`
  responsabilita': opzionale centralizzazione valori di stato e helper di relazione.
- Modify: `app/Exports/WorksExport.php`
  responsabilita': applicare scope coerente ai dati esportati.
- Modify: `app/Exports/GbxExport.php`
  responsabilita': applicare scope coerente ai dati esportati.
- Modify: `app/Exports/PermessiEnteExport.php`
  responsabilita': correggere mapping regione e scope.
- Modify: `app/Exports/DecommissioningExport.php`
  responsabilita': applicare scope coerente ai dati esportati.
- Modify: `resources/views/livewire/work-form.blade.php`
  responsabilita': usare valori `phase` centralizzati.
- Modify: `resources/views/livewire/work-edit.blade.php`
  responsabilita': usare valori `phase` centralizzati e direttive ruolo corrette.
- Modify: `resources/views/livewire/edit-work.blade.php`
  responsabilita': usare direttive ruolo corrette e download allegati non pubblici.
- Modify: `resources/views/livewire/view-work.blade.php`
  responsabilita': download allegati non pubblici.
- Modify: `resources/views/works/index.blade.php`
  responsabilita': rendere coerenti i campi export disponibili.
- Create: `app/Support/WorkOptions.php`
  responsabilita': sorgente unica per `phase`, `status`, eventuali boolean label delle lavorazioni.
- Create: `app/Policies/WorkPolicy.php`
  responsabilita': definire `view`, `update`, `operate`, `duplicate`, `complete`.
- Create: `app/Http/Controllers/MediaDownloadController.php`
  responsabilita': servire allegati tramite autorizzazione applicativa.
- Create: `database/migrations/2026_04_14_000001_add_table_performance_indexes.php`
  responsabilita': aggiungere indici per campi usati da filtri/sort.
- Modify: `app/Providers/AppServiceProvider.php`
  responsabilita': registrare eventuali view composers/helper condivisi solo se servono; evitare logica dispersa.
- Create: `tests/Feature/WorkAuthorizationTest.php`
  responsabilita': coprire export, edit, action operatore e policy lavori.
- Create: `tests/Feature/MediaAccessTest.php`
  responsabilita': verificare che gli allegati non siano accessibili pubblicamente.
- Create: `tests/Feature/UserManagementTest.php`
  responsabilita': verificare hashing password e unicita' email update.
- Modify: `tests/Feature/WorkSuspensionFeatureTest.php`
  responsabilita': ampliare casi di autorizzazione e coerenza `phase`.
- Modify: `tests/Feature/DecommissioningFeatureTest.php`
  responsabilita': aggiungere controlli export/ruolo se utile.
- Create: `tests/Feature/ExportAuthorizationTest.php`
  responsabilita': coprire accesso e whitelist dei parametri export.

### Task 1: Chiudere il perimetro HTTP e gli export pubblici

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/WorkController.php`
- Modify: `app/Http/Controllers/GbxController.php`
- Modify: `app/Http/Controllers/PermessoEnteController.php`
- Modify: `app/Http/Controllers/DecommissioningController.php`
- Test: `tests/Feature/ExportAuthorizationTest.php`

- [ ] **Step 1: Scrivere i test in fallimento per accesso export**

```php
public function test_guest_cannot_download_works_export(): void
{
    $this->get(route('exports.works', [
        'date_field' => 'created_at',
        'start' => '2026-04-01',
        'end' => '2026-04-14',
    ]))->assertRedirect('/');
}

public function test_operator_cannot_download_admin_exports(): void
{
    $this->seed(RoleSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole('operator');

    $this->actingAs($operator)
        ->get(route('exports.gbxes', [
            'date_field' => 'created_at',
            'start' => '2026-04-01',
            'end' => '2026-04-14',
        ]))
        ->assertForbidden();
}
```

- [ ] **Step 2: Eseguire i test e verificare il fallimento**

Run: `php artisan test --filter=ExportAuthorizationTest`
Expected: FAIL per route `exports.works` accessibile senza autenticazione.

- [ ] **Step 3: Spostare tutte le route export dentro gruppi protetti e aggiungere whitelist rigorose**

```php
Route::middleware(['auth', 'role:admin|supervisor'])->group(function () {
    Route::get('/works/table', [WorkController::class, 'index'])->name('works-table');
    Route::get('/works/create', [WorkController::class, 'create'])->name('addWork');
    Route::get('/works/edit/{id}', [WorkController::class, 'edit'])->name('editWork');
    Route::delete('/works/delete/{work}', [WorkController::class, 'delete'])->name('deleteWork');
    Route::get('/exports/works', [WorkController::class, 'download'])->name('exports.works');
});
```

```php
$validated = $request->validate([
    'date_field' => ['required', Rule::in(['created_at', 'acception_date', 'completion_date'])],
    'start' => ['required', 'date'],
    'end' => ['required', 'date', 'after_or_equal:start'],
]);
```

```php
$validated = $request->validate([
    'date_field' => ['required', Rule::in([
        'date',
        'created_at',
        'appointment_date',
        'inspection_date',
        'verbal_date',
        'obligation_date',
        'release_date',
        'permission_request_date',
        'permission_obtain_date',
        'project_date',
        'speedark_date',
        'cart_update_date',
    ])],
    'start' => ['required', 'date'],
    'end' => ['required', 'date', 'after_or_equal:start'],
]);
```

- [ ] **Step 4: Rieseguire i test**

Run: `php artisan test --filter=ExportAuthorizationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add routes/web.php app/Http/Controllers/WorkController.php app/Http/Controllers/GbxController.php app/Http/Controllers/PermessoEnteController.php app/Http/Controllers/DecommissioningController.php tests/Feature/ExportAuthorizationTest.php
git commit -m "fix: protect export routes and validate export filters"
```

### Task 2: Enforce server-side authorization sui lavori

**Files:**
- Create: `app/Policies/WorkPolicy.php`
- Modify: `app/Models/User.php`
- Modify: `app/Livewire/OperatorTable.php`
- Modify: `app/Livewire/EditWork.php`
- Modify: `app/Livewire/ViewWork.php`
- Test: `tests/Feature/WorkAuthorizationTest.php`

- [ ] **Step 1: Scrivere test di autorizzazione per action operatore e modal edit**

```php
public function test_operator_cannot_suspend_work_not_assigned_to_him(): void
{
    $this->seed(RoleSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole('operator');

    $otherOperator = User::factory()->create();
    $otherOperator->assignRole('operator');

    $work = Work::create(['status' => 'In Lavorazione']);
    $work->users()->attach($otherOperator->id, ['created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($operator);

    Livewire::test(OperatorTable::class)
        ->call('suspendWork', $work->id);

    $this->assertDatabaseHas('works', [
        'id' => $work->id,
        'status' => 'In Lavorazione',
    ]);
}
```

- [ ] **Step 2: Eseguire i test e verificare il fallimento**

Run: `php artisan test --filter=WorkAuthorizationTest`
Expected: FAIL per modifica stato non autorizzata.

- [ ] **Step 3: Implementare `WorkPolicy` e usare `authorize()` nei componenti**

```php
class WorkPolicy
{
    public function operate(User $user, Work $work): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor'])
            || $work->users()->whereKey($user->id)->exists();
    }

    public function update(User $user, Work $work): bool
    {
        return $this->operate($user, $work);
    }

    public function duplicate(User $user, Work $work): bool
    {
        return $user->hasAnyRole(['admin', 'supervisor']);
    }
}
```

```php
$work = Work::query()->lockForUpdate()->findOrFail($id);
$this->authorize('operate', $work);
```

```php
$this->work = Work::with(['media', 'users', 'workSuspensions'])->findOrFail($id);
$this->authorize('update', $this->work);
```

- [ ] **Step 4: Rieseguire i test target**

Run: `php artisan test --filter=WorkAuthorizationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Policies/WorkPolicy.php app/Livewire/OperatorTable.php app/Livewire/EditWork.php app/Livewire/ViewWork.php tests/Feature/WorkAuthorizationTest.php
git commit -m "fix: enforce work authorization in livewire actions"
```

### Task 3: Rendere gli allegati sicuri e non pubblici

**Files:**
- Modify: `app/Livewire/Concerns/HandlesMediaUploads.php`
- Create: `app/Http/Controllers/MediaDownloadController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/livewire/edit-work.blade.php`
- Modify: `resources/views/livewire/work-edit.blade.php`
- Modify: `resources/views/livewire/view-work.blade.php`
- Modify: `resources/views/livewire/decommissioning-form.blade.php`
- Modify: `resources/views/livewire/permesso-ente-form.blade.php`
- Test: `tests/Feature/MediaAccessTest.php`

- [ ] **Step 1: Scrivere test per download autenticato e blocco accesso diretto**

```php
public function test_work_attachment_is_downloaded_via_authorized_route(): void
{
    Storage::fake('local');
    $this->seed(RoleSeeder::class);

    $operator = User::factory()->create();
    $operator->assignRole('operator');

    $work = Work::create(['status' => 'Da Lavorare']);
    $work->users()->attach($operator->id, ['created_at' => now(), 'updated_at' => now()]);
    $media = $work->media()->create([
        'file_path' => 'works_media/test.pdf',
        'file_name' => 'test.pdf',
        'mime_type' => 'application/pdf',
        'size' => 100,
    ]);

    Storage::disk('local')->put($media->file_path, 'fake');

    $this->actingAs($operator)
        ->get(route('media.download', $media))
        ->assertOk();
}
```

- [ ] **Step 2: Eseguire il test e verificarne il fallimento**

Run: `php artisan test --filter=MediaAccessTest`
Expected: FAIL per route assente/storage pubblico.

- [ ] **Step 3: Spostare upload su storage privato e introdurre whitelist**

```php
protected function mediaUploadValidationRules(): array
{
    return [
        'files' => 'nullable|array|max:10',
        'files.*' => 'file|max:25600|mimetypes:application/pdf,image/jpeg,image/png,image/webp,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel',
    ];
}
```

```php
$path = $file->store($directory, 'local');
```

```php
Route::get('/media/{media}/download', MediaDownloadController::class)
    ->middleware('auth')
    ->name('media.download');
```

```php
return Storage::disk('local')->download($media->file_path, $media->file_name);
```

- [ ] **Step 4: Aggiornare tutte le blade per usare la route di download**

```blade
<a href="{{ route('media.download', $media->id) }}" target="_blank" class="btn btn-sm btn-outline-primary">
    <i class="bi bi-download"></i> Scarica
</a>
```

- [ ] **Step 5: Rieseguire i test**

Run: `php artisan test --filter=MediaAccessTest`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/Concerns/HandlesMediaUploads.php app/Http/Controllers/MediaDownloadController.php routes/web.php resources/views/livewire/edit-work.blade.php resources/views/livewire/work-edit.blade.php resources/views/livewire/view-work.blade.php resources/views/livewire/decommissioning-form.blade.php resources/views/livewire/permesso-ente-form.blade.php tests/Feature/MediaAccessTest.php
git commit -m "fix: serve attachments through authorized private downloads"
```

### Task 4: Correggere gestione utenti e password

**Files:**
- Modify: `app/Http/Controllers/UsersController.php`
- Test: `tests/Feature/UserManagementTest.php`

- [ ] **Step 1: Scrivere test per hashing password e email univoca in update**

```php
public function test_admin_update_hashes_new_password(): void
{
    $this->seed(RoleSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $user = User::factory()->create(['password' => bcrypt('old-password')]);

    $this->actingAs($admin)
        ->put(route('updateUser', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'new-password-123',
            'company_id' => null,
            'roles' => ['operator'],
        ])
        ->assertRedirect(route('accounts-table'));

    $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
}
```

- [ ] **Step 2: Eseguire il test e verificare il fallimento**

Run: `php artisan test --filter=UserManagementTest`
Expected: FAIL per password salvata in chiaro o per validazione email non corretta.

- [ ] **Step 3: Correggere `UsersController@update`**

```php
'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
```

```php
'password' => filled($validated['password'] ?? null)
    ? Hash::make($validated['password'])
    : $user->password,
```

- [ ] **Step 4: Rieseguire i test**

Run: `php artisan test --filter=UserManagementTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/UsersController.php tests/Feature/UserManagementTest.php
git commit -m "fix: hash updated passwords and enforce unique emails"
```

### Task 5: Normalizzare valori dominio tra form, tabelle, filtri ed export

**Files:**
- Create: `app/Support/WorkOptions.php`
- Modify: `app/Models/Work.php`
- Modify: `app/Livewire/WorksTable.php`
- Modify: `app/Livewire/OperatorTable.php`
- Modify: `resources/views/livewire/work-form.blade.php`
- Modify: `resources/views/livewire/work-edit.blade.php`
- Modify: `resources/views/livewire/edit-work.blade.php`
- Modify: `tests/Feature/WorkSuspensionFeatureTest.php`

- [ ] **Step 1: Scrivere test per coerenza `phase`**

```php
public function test_work_phase_filter_matches_values_saved_by_form(): void
{
    $this->seed(RoleSeeder::class);

    $supervisor = User::factory()->create();
    $supervisor->assignRole('supervisor');
    $this->actingAs($supervisor);

    Work::create([
        'status' => 'Da Lavorare',
        'phase' => 'Fase 1',
    ]);

    Livewire::test(WorksTable::class)
        ->setFilter('phase', 'Fase 1')
        ->assertSee('Fase 1');
}
```

- [ ] **Step 2: Eseguire il test e verificarne il fallimento**

Run: `php artisan test --filter=phase_filter_matches_values_saved_by_form`
Expected: FAIL o comportamento incoerente se il form salva `FASE 1`.

- [ ] **Step 3: Introdurre sorgente unica per le opzioni**

```php
final class WorkOptions
{
    public const PHASES = [
        'Fase 1' => 'Fase 1',
        'Fase 2' => 'Fase 2',
        'Aggiornamento' => 'Aggiornamento',
        'Modifica' => 'Modifica',
    ];
}
```

```php
public static function phaseOptions(): array
{
    return WorkOptions::PHASES;
}
```

```blade
@foreach (\App\Models\Work::phaseOptions() as $value => $label)
    <option value="{{ $value }}">{{ $label }}</option>
@endforeach
```

- [ ] **Step 4: Rieseguire i test**

Run: `php artisan test --filter=WorkSuspensionFeatureTest`
Expected: PASS sui casi gia' coperti e sul nuovo caso `phase`.

- [ ] **Step 5: Commit**

```bash
git add app/Support/WorkOptions.php app/Models/Work.php app/Livewire/WorksTable.php app/Livewire/OperatorTable.php resources/views/livewire/work-form.blade.php resources/views/livewire/work-edit.blade.php resources/views/livewire/edit-work.blade.php tests/Feature/WorkSuspensionFeatureTest.php
git commit -m "refactor: centralize work domain options"
```

### Task 6: Correggere incongruenze ruoli, export e mapping dati

**Files:**
- Modify: `app/Livewire/GbxTable.php`
- Modify: `resources/views/livewire/edit-work.blade.php`
- Modify: `resources/views/livewire/work-edit.blade.php`
- Modify: `app/Exports/PermessiEnteExport.php`
- Modify: `app/Exports/GbxExport.php`
- Modify: `app/Exports/WorksExport.php`
- Modify: `app/Exports/DecommissioningExport.php`
- Test: `tests/Feature/ExportAuthorizationTest.php`

- [ ] **Step 1: Scrivere test per role check e mapping regione**

```php
public function test_permessi_export_maps_regione_nome(): void
{
    $regione = Regione::create(['nome' => 'Lazio']);
    $permesso = PermessoEnte::create(['regione_id' => $regione->id]);

    $row = (new PermessiEnteExport('created_at', now()->toDateString(), now()->toDateString()))
        ->map($permesso->fresh('regione'));

    $this->assertSame('Lazio', $row[4]);
}
```

- [ ] **Step 2: Eseguire il test e verificarne il fallimento**

Run: `php artisan test --filter=permessi_export_maps_regione_nome`
Expected: FAIL con valore nullo in colonna regione.

- [ ] **Step 3: Correggere i check ruolo e il mapping export**

```php
->when(! auth()->user()->hasAnyRole(['admin', 'GBX Supervisor']), function (Builder $query) {
    $query->where('company_id', auth()->user()->company_id);
})
```

```php
optional($permesso->regione)->nome,
```

```blade
@hasanyrole('admin|supervisor')
    @include('livewire.partials.work-suspensions-editor')
@endhasanyrole
```

- [ ] **Step 4: Rieseguire i test**

Run: `php artisan test --filter=ExportAuthorizationTest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Livewire/GbxTable.php resources/views/livewire/edit-work.blade.php resources/views/livewire/work-edit.blade.php app/Exports/PermessiEnteExport.php app/Exports/GbxExport.php app/Exports/WorksExport.php app/Exports/DecommissioningExport.php tests/Feature/ExportAuthorizationTest.php
git commit -m "fix: align role checks and export mappings"
```

### Task 7: Ottimizzare query e indici per tabelle desktop

**Files:**
- Modify: `app/Livewire/WorksTable.php`
- Modify: `app/Livewire/OperatorTable.php`
- Modify: `app/Livewire/PermessiEnteTable.php`
- Modify: `app/Livewire/DecommissioningTable.php`
- Create: `database/migrations/2026_04_14_000001_add_table_performance_indexes.php`
- Test: `tests/Feature/WorkSuspensionFeatureTest.php`

- [ ] **Step 1: Scrivere una verifica minima sul numero di query nei punti peggiori**

```php
DB::enableQueryLog();

Livewire::test(WorksTable::class)->render();

$this->assertLessThan(15, count(DB::getQueryLog()));
```

- [ ] **Step 2: Eseguire il test e osservare il baseline**

Run: `php artisan test --filter=works_table_query_count`
Expected: FAIL o baseline alta.

- [ ] **Step 3: Alleggerire `builder()` e aggiungere indici**

```php
return Work::query()
    ->select([
        'works.id',
        'works.company_id',
        'works.central_id',
        'works.status',
        'works.network',
        'works.ao_cno',
        'works.description',
        'works.ntw_scope',
        'works.type',
        'works.phase',
        'works.daphne',
        'works.tempo_daphne',
        'works.company_assistant',
        'works.completion_date',
        'works.expected_delivery_date',
        'works.acception_date',
        'works.delivery_date',
        'works.nroe',
        'works.wo_number',
        'works.unica_number',
        'works.notes',
        'works.created_at',
    ])
    ->with(['company:id,name', 'central:id,central,region', 'users:id,name'])
    ->withCount('media');
```

```php
$table->index(['company_id', 'created_at']);
$table->index(['central_id', 'created_at']);
$table->index(['expected_delivery_date']);
$table->index(['delivery_date']);
$table->index(['acception_date']);
```

- [ ] **Step 4: Rimuovere `->get()` inutili nei filtri**

```php
$builder->whereHas('company', function (Builder $query) use ($value) {
    $query->where('name', 'like', "%{$value}%");
});
```

- [ ] **Step 5: Rieseguire i test**

Run: `php artisan test --filter=works_table_query_count`
Expected: PASS o query count sensibilmente ridotto.

- [ ] **Step 6: Commit**

```bash
git add app/Livewire/WorksTable.php app/Livewire/OperatorTable.php app/Livewire/PermessiEnteTable.php app/Livewire/DecommissioningTable.php database/migrations/2026_04_14_000001_add_table_performance_indexes.php tests/Feature/WorkSuspensionFeatureTest.php
git commit -m "perf: optimize table queries and add indexes"
```

### Task 8: Rendere la suite eseguibile e chiudere il giro di verifica

**Files:**
- Modify: `phpunit.xml`
- Modify: `README.md`
- Modify: `tests/TestCase.php`
- Test: intera suite feature

- [ ] **Step 1: Documentare precondizioni minime per i test feature**

```md
## Test

Per eseguire i test feature servono:
- estensione PHP `pdo_sqlite`
- file database SQLite di test o database dedicato

Comando:
`php artisan test`
```

- [ ] **Step 2: Rendere esplicita la configurazione di test**

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 3: Eseguire la suite completa**

Run: `php artisan test`
Expected: tutti i test feature in esecuzione, senza skip dovuti a `pdo_sqlite`.

- [ ] **Step 4: Verifica manuale desktop**

Run:
- `php artisan serve`
- aprire le viste tabellari principali

Expected:
- export accessibili solo ai ruoli corretti
- operatori impossibilitati a mutare record non assegnati
- allegati scaricabili solo da utenti autorizzati
- filtri `phase` coerenti con i valori salvati
- tabella lavori fluida anche con page size elevato

- [ ] **Step 5: Commit**

```bash
git add phpunit.xml README.md tests/TestCase.php
git commit -m "chore: document and stabilize test environment"
```

## Self-Review

- Copertura requisiti:
  sicurezza: Task 1, 2, 3, 4.
  incongruenze logiche tra entita': Task 5, 6.
  velocita' e tabelle desktop: Task 7.
  affidabilita' e regressioni: Task 8.
- Placeholder scan:
  nessun `TODO`, nessun rimando implicito, ogni task ha file, step, snippet e comando.
- Type consistency:
  il piano usa sempre `WorkPolicy`, `WorkOptions`, `MediaDownloadController`, `ExportAuthorizationTest`, `WorkAuthorizationTest`, `MediaAccessTest`, `UserManagementTest`.

## Recommended Execution Order

1. Task 1
2. Task 2
3. Task 3
4. Task 4
5. Task 5
6. Task 6
7. Task 7
8. Task 8

Questo ordine minimizza il rischio: prima si chiudono i buchi di sicurezza, poi si riallinea il dominio, infine si ottimizzano performance e test.
