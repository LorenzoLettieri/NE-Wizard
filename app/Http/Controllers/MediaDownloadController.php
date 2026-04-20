<?php

namespace App\Http\Controllers;

use App\Models\Decommissioning;
use App\Models\Gbx;
use App\Models\Media;
use App\Models\PermessoEnte;
use App\Models\Work;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaDownloadController extends Controller
{
    public function __invoke(Request $request, Media $media): StreamedResponse
    {
        $mediable = $media->mediable;

        abort_if($mediable === null, 404);

        $this->authorizeDownload($request, $mediable);

        try {
            $disk = $media->ensurePrivateAvailability();
        } catch (RuntimeException $exception) {
            report($exception);

            abort(500, 'Unable to prepare media for download.');
        }

        abort_if($disk === null, 404);

        $headers = [];

        if ($media->mime_type) {
            $headers['Content-Type'] = $media->mime_type;
        }

        return Storage::disk($disk)->download($media->file_path, $media->file_name, $headers);
    }

    private function authorizeDownload(Request $request, mixed $mediable): void
    {
        $user = $request->user();

        abort_unless($user, 401);

        $authorized = match (true) {
            $mediable instanceof Work => $user->can('view', $mediable),
            $mediable instanceof Gbx => $user->hasAnyRole(['admin', 'GBX', 'GBX Supervisor']),
            $mediable instanceof PermessoEnte => $user->hasAnyRole(['admin', 'permessi ente']),
            $mediable instanceof Decommissioning => $user->hasAnyRole(['admin', 'Deco']),
            default => false,
        };

        abort_unless($authorized, 403);
    }
}
