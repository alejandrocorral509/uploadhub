<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Category;
use App\Models\PlaybackProgress;
use Illuminate\Http\Request;
use Illuminate\View\View;

// "Tu biblioteca": tus episodios y programas, favoritos, suscripciones y lo que estás escuchando
class LibraryController extends Controller
{
    public const TABS = [
        'episodios' => 'Mis episodios',
        'programas' => 'Mis programas',
        'favoritos' => 'Favoritos',
        'suscripciones' => 'Suscripciones',
        'escuchando' => 'Continuar escuchando',
    ];

    public const SORTS = [
        'created_at' => 'Fecha',
        'title' => 'Título',
        'plays_count' => 'Reproducciones',
        'likes_count' => 'Me gusta',
    ];

    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $tab = array_key_exists($request->query('tab'), self::TABS) ? $request->query('tab') : 'episodios';

        $data = match ($tab) {
            'episodios' => $this->myEpisodes($request),
            'programas' => ['shows' => $user->shows()->withCount(['episodes', 'subscribers'])->latest()->get()],
            'favoritos' => ['episodes' => $user->likedAudios()->visibleTo($user)
                ->with(['user', 'show'])->withCount('likes')
                ->orderByPivot('created_at', 'desc')->paginate(12)->withQueryString()],
            'suscripciones' => [
                'subscriptions' => $user->subscriptions()->with('user')->orderBy('title')->get(),
                'episodes' => Audio::public()
                    ->whereIn('show_id', $user->subscriptions()->select('shows.id'))
                    ->with(['user', 'show'])->withCount('likes')
                    ->latest()->paginate(12)->withQueryString(),
            ],
            'escuchando' => ['episodes' => Audio::visibleTo($user)
                ->with(['user', 'show'])->withCount('likes')
                ->join('playback_progress as pp', 'pp.audio_id', '=', 'audios.id')
                ->where('pp.user_id', $user->id)
                ->where('pp.completed', false)
                ->orderByDesc('pp.updated_at')
                ->select('audios.*')
                ->paginate(12)->withQueryString()],
        };

        $episodes = $data['episodes'] ?? collect();

        return view('library', $data + [
            'tab' => $tab,
            'tabs' => self::TABS,
            'summary' => [
                'episodes' => $user->audios()->count(),
                'plays' => (int) $user->audios()->sum('plays_count'),
                'likes' => Audio::where('audios.user_id', $user->id)->join('audio_user_likes', 'audio_user_likes.audio_id', '=', 'audios.id')->count(),
                'subscribers' => $user->shows()->join('subscriptions', 'subscriptions.show_id', '=', 'shows.id')->count(),
            ],
            'positions' => PlaybackProgress::positionsFor($user, $episodes),
        ]);
    }

    private function myEpisodes(Request $request): array
    {
        $sort = array_key_exists($request->query('orden'), self::SORTS) ? $request->query('orden') : 'created_at';
        $direction = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $episodes = $request->user()->audios()
            ->with(['show', 'categories', 'tags', 'user'])
            ->withCount(['likes', 'comments'])
            ->search($request->query('q'))
            ->when($request->integer('categoria'), fn ($q, $id) => $q->whereHas('categories', fn ($c) => $c->whereKey($id)))
            ->when(in_array($request->query('privacidad'), ['public', 'private'], true), fn ($q) => $q->where('privacy', $request->query('privacidad')))
            ->orderBy($sort, $direction)
            ->paginate(10)
            ->withQueryString();

        return [
            'episodes' => $episodes,
            'categories' => Category::orderBy('name')->get(),
            'sorts' => self::SORTS,
            'sort' => $sort,
            'direction' => $direction,
        ];
    }
}
