<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Category;
use App\Models\PlaybackProgress;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $base = fn () => Audio::public()->with(['user', 'show'])->withCount('likes');

        $popular = $base()->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('plays_count')->orderByDesc('likes_count')->limit(6)->get();
        $latest = $base()->latest()->limit(6)->get();

        $continue = $user
            ? Audio::visibleTo($user)
                ->with(['user', 'show'])
                ->join('playback_progress as pp', 'pp.audio_id', '=', 'audios.id')
                ->where('pp.user_id', $user->id)
                ->where('pp.completed', false)
                ->where('pp.position', '>', 10)
                ->orderByDesc('pp.updated_at')
                ->select('audios.*')
                ->limit(4)
                ->get()
            : collect();

        $episodes = $popular->concat($latest)->concat($continue);

        return view('home', [
            'popular' => $popular,
            'latest' => $latest,
            'continue' => $continue,
            'shows' => Show::with('user')->withCount(['episodes' => fn ($q) => $q->public(), 'subscribers'])
                ->whereHas('episodes', fn ($q) => $q->public())->orderByDesc('subscribers_count')->limit(6)->get(),
            'categories' => Category::withCount(['audios' => fn ($q) => $q->public()])->orderByDesc('audios_count')->limit(10)->get(),
            'stats' => [
                'episodes' => Audio::public()->count(),
                'shows' => Show::count(),
                'plays' => (int) Audio::public()->sum('plays_count'),
            ],
            'positions' => PlaybackProgress::positionsFor($user, $episodes),
        ]);
    }
}
