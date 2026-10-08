<?php

namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __invoke(Request $request, Show $show): JsonResponse
    {
        $result = $show->subscribers()->toggle($request->user()->id);

        return response()->json([
            'subscribed' => $result['attached'] !== [],
            'subscribers_count' => $show->subscribers()->count(),
        ]);
    }
}
