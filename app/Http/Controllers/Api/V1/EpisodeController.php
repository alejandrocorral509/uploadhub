<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CommentResource;
use App\Http\Resources\EpisodeResource;
use App\Models\Audio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EpisodeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $episodes = Audio::public()
            ->with(['user', 'show', 'categories', 'tags'])
            ->withCount(['likes', 'comments'])
            ->search($request->query('q'))
            ->when($request->integer('category'), fn ($q, $id) => $q->whereHas('categories', fn ($c) => $c->whereKey($id)))
            ->when($request->query('sort') === 'popular', fn ($q) => $q->orderByDesc('plays_count'))
            ->latest()
            ->paginate(min(50, max(1, $request->integer('per_page', 20))))
            ->withQueryString();

        return EpisodeResource::collection($episodes);
    }

    public function show(Audio $audio): EpisodeResource
    {
        Gate::authorize('view', $audio);

        return new EpisodeResource($audio->load(['user', 'show', 'categories', 'tags'])->loadCount(['likes', 'comments']));
    }

    // Últimos episodios de los programas a los que está suscrito el usuario
    public function feed(Request $request): AnonymousResourceCollection
    {
        $episodes = Audio::public()
            ->whereIn('show_id', $request->user()->subscriptions()->select('shows.id'))
            ->with(['user', 'show'])
            ->withCount('likes')
            ->latest()
            ->paginate(20);

        return EpisodeResource::collection($episodes);
    }

    public function like(Request $request, Audio $audio): JsonResponse
    {
        Gate::authorize('view', $audio);

        $result = $audio->likes()->toggle($request->user()->id);

        return response()->json([
            'liked' => $result['attached'] !== [],
            'likes_count' => $audio->likes()->count(),
        ]);
    }

    public function comments(Audio $audio): AnonymousResourceCollection
    {
        Gate::authorize('view', $audio);

        return CommentResource::collection($audio->comments()->with('user')->latest()->paginate(20));
    }

    public function comment(Request $request, Audio $audio): CommentResource
    {
        Gate::authorize('view', $audio);

        $data = $request->validate(['content' => ['required', 'string', 'max:1000']]);

        $comment = $audio->comments()->create(['user_id' => $request->user()->id, 'content' => $data['content']]);

        return new CommentResource($comment->load('user'));
    }
}
