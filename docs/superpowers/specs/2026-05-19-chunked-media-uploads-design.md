# Chunked Media Uploads Design

## Goal

Allow users to upload attachments up to 500 MB per file without relying on Livewire's normal temporary file upload path. The upload experience must support progress feedback and retries, keep existing private media storage, and preserve current authorization rules for Works, GBX, Permessi Ente, and Decommissioning records.

The feature keeps multiple attachments enabled, but treats 500 MB as the per-file limit. A conservative total upload cap per record operation should be enforced to avoid accidental multi-GB submissions.

## Recommended Approach

Use FilePond with chunk uploads integrated into the existing Blade/Livewire forms. FilePond handles browser-side chunking, progress, retry, and cancellation. Laravel exposes authenticated endpoints for upload initialization, chunk transfer, completion, and cancellation.

This replaces `wire:model="files"` for media uploads. Livewire remains responsible for the record form state, validation, saves, existing media display, and deletion toggles.

## User Experience

Each media section shows a file picker with per-file progress. Files can be queued, uploaded, retried, or removed before completion. The form should prevent final save while any selected upload is incomplete or failed.

Completed uploads should appear as pending uploaded attachments in the current form session. For edit forms, a completed upload can be attached immediately to the existing record. For create forms, completed uploads are staged under a Livewire form token and then linked when the parent record is created.

Messages must reflect the new limit: 500 MB per attachment, current file count limit, and total upload cap.

## Backend Architecture

Add a small upload session layer. Each session is tied to:

- authenticated user id
- target media context, such as `work`, `gbx`, `permesso_ente`, or `decommissioning`
- target model id for edit flows, or a temporary form token for create flows
- original file name, MIME type, expected size, chunk size, and expected chunk count
- status: pending, uploading, completed, cancelled, expired

Chunks are written under private temporary storage, for example:

`storage/app/private/chunked_uploads/{upload_session_id}/chunks/{index}.part`

On completion, Laravel validates that all chunks exist and the assembled size matches the expected file size. The final file is streamed into the same private disk currently used by `App\Models\Media`, then a media row is created immediately for edit flows or staged against the form token for create flows.

Temporary chunks older than 24 hours should be cleaned by an Artisan command that can be scheduled.

## Authorization

Every endpoint must require an authenticated user. Initialization and completion must check the same business permissions used by the relevant form or download flow.

For edit flows, the user must be authorized for the target existing record before the upload session is created. For create flows, uploads are allowed only for users who can access the relevant create form, and staged uploads must be linked only to a record created by the same user/session.

Upload session ids must be random, non-sequential values. Chunk endpoints must reject sessions owned by another user, completed sessions, expired sessions, and invalid chunk indexes.

## Validation

Per-file maximum: 500 MB.

Recommended total cap per form operation: 1 GB. This accommodates the expected case of one large file plus smaller attachments while preventing accidental 5 GB submissions from the existing 10-file allowance.

Allowed file types are unrestricted. The backend still sanitizes file names and enforces size, ownership, context, and quota checks.

Server validation should check:

- expected file size and actual assembled size
- sanitized original file name and reported MIME type
- maximum chunk count
- duplicate or out-of-range chunk indexes
- storage write failures

## Data Model

Keep the existing `media` table as the source of truth for completed attachments. Add a dedicated table for upload sessions rather than overloading `media` with incomplete files.

Suggested table: `media_upload_sessions`

Fields:

- `id` as UUID or ULID
- `user_id`
- `context`
- `mediable_type` nullable for create flows
- `mediable_id` nullable for create flows
- `form_token` nullable for edit flows
- `original_name`
- `mime_type`
- `size`
- `chunk_size`
- `chunk_count`
- `received_chunks`
- `status`
- `final_path` nullable
- `expires_at`
- timestamps

For create flows, keep `mediable_*` null until the form is saved and link completed sessions through a temporary form token owned by the user. When the parent record is created, the Livewire component claims only completed sessions with the same user id, context, and form token.

## Integration Points

Replace the existing file inputs in:

- `resources/views/livewire/work-form.blade.php`
- `resources/views/livewire/work-edit.blade.php`
- `resources/views/livewire/edit-work.blade.php`
- `resources/views/livewire/gbx-form.blade.php`
- `resources/views/livewire/gbx-edit.blade.php`
- `resources/views/livewire/permesso-ente-form.blade.php`
- `resources/views/livewire/decommissioning-form.blade.php`

Refactor `App\Livewire\Concerns\HandlesMediaUploads` so it handles completed upload session ids instead of `TemporaryUploadedFile` instances for the chunked path. Existing media deletion behavior remains unchanged.

## Error Handling

If a chunk upload fails, the frontend library should allow retry. If completion fails, the upload session remains failed or pending with a clear message and can be retried or cancelled.

If the DB write fails after the final file is assembled, Laravel must delete the final file and mark the upload session as failed.

If the user leaves the page, incomplete chunks remain temporary and are removed by cleanup.

## Testing

Add feature tests for:

- upload initialization rejects files over 500 MB
- unauthorized users cannot create or complete sessions for protected records
- chunk endpoint rejects invalid indexes and wrong owners
- completion assembles chunks, writes the final private file, and creates or stages media metadata
- cancellation removes temporary chunks
- cleanup removes expired incomplete sessions and files

Add focused frontend checks where practical for the upload component state: queued, uploading, completed, failed, and blocking save while incomplete.

## Deployment Notes

The backend still needs infrastructure limits compatible with chunk size, not 500 MB full request size. For 10 MB chunks, configure PHP and web server limits comfortably above 10 MB, such as 20-30 MB, with timeouts appropriate for slow networks.

Storage capacity must be monitored for both final media and temporary chunks.
