<div wire:key="chunked-media-upload-{{ $mediaUploadContext }}-{{ $mediaUploadModelId ?? 'new' }}-{{ $mediaUploadFormToken }}">
    <div class="chunked-media-upload" wire:ignore>
        <label class="form-label">{{ $label ?? 'Allegati' }}</label>
        <input
            type="file"
            class="js-chunked-media-upload"
            multiple
            data-media-upload-context="{{ $mediaUploadContext }}"
            data-media-upload-model-id="{{ $mediaUploadModelId }}"
            data-media-upload-form-token="{{ $mediaUploadFormToken }}"
        >
        <div class="form-text">Massimo 500 MB per allegato. Il caricamento viene completato prima del salvataggio.</div>
    </div>
</div>
