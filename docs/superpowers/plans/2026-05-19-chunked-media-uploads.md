# Chunked Media Uploads Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add FilePond chunked uploads for private media attachments up to 500 MB per file.

**Architecture:** FilePond replaces Livewire's `wire:model="files"` media input. Laravel owns upload sessions, receives FilePond `POST`/`PATCH`/`HEAD`/`DELETE` requests, assembles chunks into the private disk, and either creates media immediately for edit flows or stages uploads by form token for create flows.

**Tech Stack:** Laravel 12, Livewire 3, Vite, FilePond, PHPUnit.

---

### Task 1: Upload Session Persistence

**Files:**
- Create: `database/migrations/2026_05_19_130000_create_media_upload_sessions_table.php`
- Create: `app/Models/MediaUploadSession.php`
- Test: `tests/Feature/ChunkedMediaUploadTest.php`

- [ ] **Step 1: Write failing persistence tests**

Create `tests/Feature/ChunkedMediaUploadTest.php` with tests that assert a media upload session can be stored with a string id, user id, context, file metadata, chunk metadata, and status.

- [ ] **Step 2: Run the focused test**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: fail because `MediaUploadSession` and the table do not exist.

- [ ] **Step 3: Add the migration and model**

Create `media_upload_sessions` with string primary key, `user_id`, `context`, nullable `mediable_type`, nullable `mediable_id`, nullable `form_token`, file metadata, chunk counters, `status`, nullable `final_path`, nullable `completed_at`, `expires_at`, and timestamps.

- [ ] **Step 4: Run the focused test again**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: pass.

### Task 2: Backend Chunk Protocol

**Files:**
- Create: `app/Services/ChunkedMediaUploadService.php`
- Create: `app/Http/Controllers/ChunkedMediaUploadController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/ChunkedMediaUploadTest.php`

- [ ] **Step 1: Write failing endpoint tests**

Cover:
- guests are redirected
- initialization rejects files over 500 MB
- initialization creates a session and returns the FilePond transfer id
- `PATCH` writes a chunk at the requested `Upload-Offset`
- `HEAD` returns the next upload offset
- completion assembles chunks and creates media for edit flows
- `DELETE` cancels an upload and removes temporary chunks

- [ ] **Step 2: Run the focused test**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: fail because routes and controller do not exist.

- [ ] **Step 3: Implement the service**

Implement:
- constants for `MAX_FILE_BYTES = 524288000`, `TOTAL_FORM_BYTES = 1073741824`, `DEFAULT_CHUNK_BYTES = 10485760`
- context-to-model and context-to-directory maps
- context authorization for Work, GBX, Permesso Ente, and Decommissioning
- session initialization from FilePond headers and request metadata
- chunk write to `chunked_uploads/{sessionId}/chunks/{offset}.part`
- offset calculation from received chunks
- assembly into final private media path
- media creation for edit flow
- cancellation and cleanup helpers

- [ ] **Step 4: Implement the controller and routes**

Add authenticated routes under `/media/uploads`:
- `POST /media/uploads/process`
- `PATCH /media/uploads/process/{session}`
- `HEAD /media/uploads/process/{session}`
- `DELETE /media/uploads/revert`

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: pass.

### Task 3: Livewire Claiming and Form Tokens

**Files:**
- Modify: `app/Livewire/Concerns/HandlesMediaUploads.php`
- Modify: `app/Livewire/WorkForm.php`
- Modify: `app/Livewire/GbxForm.php`
- Modify: `app/Livewire/PermessoEnteForm.php`
- Modify: `app/Livewire/DecommissioningForm.php`
- Modify: `app/Livewire/WorkEdit.php`
- Modify: `app/Livewire/EditWork.php`
- Modify: `app/Livewire/GbxEdit.php`
- Test: `tests/Feature/ChunkedMediaUploadTest.php`

- [ ] **Step 1: Write failing claim tests**

Assert completed sessions with the same user/context/form token are linked to a newly created model, while sessions for another user or token are ignored.

- [ ] **Step 2: Run the focused test**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: fail because the claim helper does not exist.

- [ ] **Step 3: Refactor the upload trait**

Add:
- `public string $mediaUploadContext`
- `public ?int $mediaUploadModelId`
- `public string $mediaUploadFormToken`
- `public array $completedUploadSessionIds`
- `initializeChunkedMediaUploads(string $context, ?int $modelId = null)`
- `claimCompletedUploadSessions(Model $model, string $directory)`
- validation that there are no incomplete sessions before form save

- [ ] **Step 4: Wire form components**

Call `initializeChunkedMediaUploads()` in every media-enabled component `mount()`. Replace `persistUploadedFiles()` calls with `claimCompletedUploadSessions()`. Keep deletion behavior unchanged.

- [ ] **Step 5: Run focused tests**

Run: `php artisan test tests/Feature/ChunkedMediaUploadTest.php`

Expected: pass.

### Task 4: FilePond Frontend

**Files:**
- Modify: `package.json`
- Modify: `resources/js/app.js`
- Modify: `resources/css/app.css`
- Create: `resources/views/livewire/partials/chunked-media-upload.blade.php`
- Modify the seven media form views listed in the design spec

- [ ] **Step 1: Install FilePond**

Run: `npm install filepond`

- [ ] **Step 2: Add the shared partial**

Create one Blade partial that renders a FilePond input with `data-media-upload-context`, `data-media-upload-model-id`, and `data-media-upload-form-token` attributes.

- [ ] **Step 3: Initialize FilePond from Vite**

In `resources/js/app.js`, import FilePond CSS and JS, initialize `.js-chunked-media-upload`, configure `chunkUploads: true`, `chunkSize: 10485760`, `maxFileSize: 500MB`, authenticated headers, FilePond process/revert URLs, and Livewire events for completed/removed files.

- [ ] **Step 4: Replace old media inputs**

Replace `wire:model="files"` sections with the shared partial, keeping existing-media lists and removal buttons.

- [ ] **Step 5: Run frontend build**

Run: `npm run build`

Expected: Vite build succeeds.

### Task 5: Cleanup Command and Regression Verification

**Files:**
- Create: `app/Console/Commands/CleanupChunkedMediaUploads.php`
- Modify: `routes/console.php`
- Test: `tests/Feature/ChunkedMediaUploadTest.php`

- [ ] **Step 1: Write cleanup test**

Assert expired incomplete sessions and their temporary chunk directories are removed, while completed sessions remain.

- [ ] **Step 2: Implement command**

Add `media:cleanup-chunked-uploads` with an `--hours=24` option.

- [ ] **Step 3: Register schedule**

Add a scheduled daily command in `routes/console.php`.

- [ ] **Step 4: Run backend tests and build**

Run:
- `php artisan test tests/Feature/ChunkedMediaUploadTest.php tests/Feature/MediaAccessTest.php`
- `npm run build`

Expected: all pass.
