<?php

namespace App\Http\Controllers;

use App\Services\ChunkedUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function start(Request $request, ChunkedUpload $uploads): JsonResponse
    {
        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'size' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json($uploads->start($request->user(), $data['filename'], (int) $data['size']));
    }

    public function chunk(Request $request, string $token, ChunkedUpload $uploads): JsonResponse
    {
        $data = $request->validate([
            'offset' => ['required', 'integer', 'min:0'],
            'chunk' => ['required', 'file', 'max:'.(config('media.chunk_mb') * 1024 + 64)],
        ]);

        return response()->json($uploads->append($token, $request->user(), (int) $data['offset'], $request->file('chunk')));
    }

    public function cancel(Request $request, string $token, ChunkedUpload $uploads): JsonResponse
    {
        $uploads->cancel($token, $request->user());

        return response()->json(['ok' => true]);
    }
}
