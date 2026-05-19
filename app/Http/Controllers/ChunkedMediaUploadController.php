<?php

namespace App\Http\Controllers;

use App\Models\MediaUploadSession;
use App\Services\ChunkedMediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChunkedMediaUploadController extends Controller
{
    public function process(Request $request, ChunkedMediaUploadService $uploads): Response
    {
        $session = $uploads->initialize($request->user(), $request);

        return response($session->id, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }

    public function patch(Request $request, MediaUploadSession $session, ChunkedMediaUploadService $uploads): Response
    {
        $nextOffset = $uploads->writeChunk($request->user(), $session, $request);

        return response('', 200, [
            'Upload-Offset' => (string) $nextOffset,
        ]);
    }

    public function head(Request $request, MediaUploadSession $session, ChunkedMediaUploadService $uploads): Response
    {
        abort_unless((int) $session->user_id === (int) $request->user()->id, 403);

        return response('', 200, [
            'Upload-Offset' => (string) $uploads->nextOffset($session),
        ]);
    }

    public function revert(Request $request, ChunkedMediaUploadService $uploads): Response
    {
        $uploads->cancel($request->user(), trim($request->getContent()));

        return response('', 204);
    }
}
