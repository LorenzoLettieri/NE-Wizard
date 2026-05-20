<?php

namespace Tests\Unit;

use Tests\TestCase;

class ChunkedMediaUploadViewTest extends TestCase
{
    public function test_chunked_media_upload_partial_rerenders_when_upload_target_changes(): void
    {
        $html = file_get_contents(resource_path('views/livewire/partials/chunked-media-upload.blade.php'));

        $this->assertStringContainsString('wire:key="chunked-media-upload-', $html);
        $this->assertStringContainsString('$mediaUploadModelId', $html);
        $this->assertStringContainsString('$mediaUploadFormToken', $html);
        $this->assertStringContainsString('wire:ignore', $html);
    }
}
