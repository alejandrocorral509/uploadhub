<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LikeController extends Controller
{
    public function __invoke(Request $request, Audio $audio): JsonResponse
    {
        Gate::authorize('view', $audio);

        $result = $audio->likes()->toggle($request->user()->id);

        return response()->json([
            'liked' => $result['attached'] !== [],
            'likes_count' => $audio->likes()->count(),
        ]);
    }
}
