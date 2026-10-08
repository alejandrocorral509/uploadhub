<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\EpisodeResource;
use App\Http\Resources\ShowResource;
use App\Models\Show;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShowController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $shows = Show::with(['user', 'category'])
            ->withCount(['episodes' => fn ($q) => $q->public(), 'subscribers'])
            ->whereHas('episodes', fn ($q) => $q->public())
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->orderByDesc('subscribers_count')
            ->paginate(20)
            ->withQueryString();

        return ShowResource::collection($shows);
    }

    public function show(Show $show): ShowResource
    {
        return new ShowResource($show->load(['user', 'category'])->loadCount(['subscribers']));
    }

    public function episodes(Request $request, Show $show): AnonymousResourceCollection
    {
        $episodes = $show->episodes()
            ->visibleTo($request->user())
            ->with(['user', 'show'])
            ->withCount('likes')
            ->latest()
            ->paginate(20);

        return EpisodeResource::collection($episodes);
    }

    public function subscribe(Request $request, Show $show): JsonResponse
    {
        $result = $show->subscribers()->toggle($request->user()->id);

        return response()->json([
            'subscribed' => $result['attached'] !== [],
            'subscribers_count' => $show->subscribers()->count(),
        ]);
    }
}
