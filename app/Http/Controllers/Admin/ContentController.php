<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\View\View;

// Listados de episodios y comentarios para moderar (el borrado usa las rutas normales: la política deja a los administradores)
class ContentController extends Controller
{
    public function episodes(Request $request): View
    {
        $episodes = Audio::with(['user', 'show'])
            ->withCount(['reports', 'comments'])
            ->search($request->query('q'))
            ->when($request->query('estado') === 'fallidos', fn ($q) => $q->where('processing_status', Audio::STATUS_FAILED))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.episodes', ['episodes' => $episodes]);
    }

    public function comments(Request $request): View
    {
        $comments = Comment::with(['user', 'audio'])
            ->withCount('reports')
            ->when($request->query('q'), fn ($q, $term) => $q->where('content', 'like', "%{$term}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.comments', ['comments' => $comments]);
    }
}
