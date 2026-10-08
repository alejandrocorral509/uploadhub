<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Category;
use App\Models\PlaybackProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExploreController extends Controller
{
    public const SORTS = [
        'recientes' => 'Más recientes',
        'populares' => 'Más me gusta',
        'escuchados' => 'Más escuchados',
    ];

    public function __invoke(Request $request): View
    {
        $sort = array_key_exists($request->query('orden'), self::SORTS) ? $request->query('orden') : 'recientes';
        $categoryId = $request->integer('categoria') ?: null;

        $episodes = Audio::public()
            ->with(['user', 'show', 'categories', 'tags'])
            ->withCount(['likes', 'comments'])
            ->search($request->query('q'))
            ->when($categoryId, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereKey($categoryId)))
            ->when($sort === 'populares', fn ($q) => $q->orderByDesc('likes_count'))
            ->when($sort === 'escuchados', fn ($q) => $q->orderByDesc('plays_count'))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('explore', [
            'episodes' => $episodes,
            'categories' => Category::orderBy('name')->get(),
            'sorts' => self::SORTS,
            'sort' => $sort,
            'categoryId' => $categoryId,
            'positions' => PlaybackProgress::positionsFor($request->user(), $episodes),
        ]);
    }
}
