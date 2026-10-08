<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Comment;
use App\Models\Play;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

// Estadísticas para quien publica: escuchas, oyentes, cuánto escuchan y dónde abandonan
class StatsController extends Controller
{
    public const PERIODS = [7 => '7 días', 30 => '30 días', 90 => '90 días', 365 => '1 año'];

    // Fracción escuchada de cada reproducción (0-1), en SQL que funciona en SQLite y MySQL
    private const COMPLETION_SQL = 'CASE WHEN audios.duration IS NULL OR audios.duration = 0 THEN NULL '
        .'WHEN plays.max_position >= audios.duration THEN 1.0 '
        .'ELSE plays.max_position * 1.0 / audios.duration END';

    public function index(Request $request): View
    {
        $user = $request->user();
        $days = $this->period($request);
        $from = now()->subDays($days - 1)->startOfDay();

        $plays = Play::query()
            ->join('audios', 'audios.id', '=', 'plays.audio_id')
            ->where('audios.user_id', $user->id)
            ->where('plays.created_at', '>=', $from);

        $topEpisodes = (clone $plays)
            ->selectRaw('audios.id, COUNT(*) as period_plays, COUNT(DISTINCT plays.listener_hash) as listeners, AVG('.self::COMPLETION_SQL.') as completion')
            ->groupBy('audios.id')
            ->orderByDesc('period_plays')
            ->limit(10)
            ->get()
            ->keyBy('id');

        $episodes = Audio::whereIn('id', $topEpisodes->keys())->with(['show', 'user'])->get()
            ->map(fn (Audio $a) => tap($a, function ($a) use ($topEpisodes) {
                $row = $topEpisodes[$a->id];
                $a->setAttribute('period_plays', (int) $row->period_plays);
                $a->setAttribute('listeners', (int) $row->listeners);
                $a->setAttribute('completion', $row->completion !== null ? (float) $row->completion : null);
            }))
            ->sortByDesc('period_plays')
            ->values();

        return view('stats.index', [
            'days' => $days,
            'periods' => self::PERIODS,
            'totals' => [
                'plays' => (clone $plays)->count(),
                'listeners' => (clone $plays)->distinct()->count('plays.listener_hash'),
                'completion' => (clone $plays)->selectRaw('AVG('.self::COMPLETION_SQL.') as c')->value('c'),
                'likes' => DB::table('audio_user_likes')
                    ->join('audios', 'audios.id', '=', 'audio_user_likes.audio_id')
                    ->where('audios.user_id', $user->id)
                    ->where('audio_user_likes.created_at', '>=', $from)->count(),
                'subscribers' => DB::table('subscriptions')
                    ->join('shows', 'shows.id', '=', 'subscriptions.show_id')
                    ->where('shows.user_id', $user->id)
                    ->where('subscriptions.created_at', '>=', $from)->count(),
                'comments' => Comment::whereHas('audio', fn ($q) => $q->where('user_id', $user->id))
                    ->where('created_at', '>=', $from)->count(),
            ],
            'daily' => $this->daily(clone $plays, $from, $days),
            'episodes' => $episodes,
        ]);
    }

    public function episode(Request $request, Audio $audio): View
    {
        Gate::authorize('viewStats', $audio);

        $days = $this->period($request);
        $from = now()->subDays($days - 1)->startOfDay();
        $audio->load(['show', 'user'])->loadCount(['likes', 'comments']);

        $plays = Play::query()
            ->join('audios', 'audios.id', '=', 'plays.audio_id')
            ->where('plays.audio_id', $audio->id)
            ->where('plays.created_at', '>=', $from);

        return view('stats.episode', [
            'audio' => $audio,
            'days' => $days,
            'periods' => self::PERIODS,
            'totals' => [
                'plays' => (clone $plays)->count(),
                'listeners' => (clone $plays)->distinct()->count('plays.listener_hash'),
                'completion' => (clone $plays)->selectRaw('AVG('.self::COMPLETION_SQL.') as c')->value('c'),
            ],
            'daily' => $this->daily(clone $plays, $from, $days),
            'retention' => $this->retention($audio, $from),
        ]);
    }

    private function period(Request $request): int
    {
        $days = $request->integer('dias', 30);

        return array_key_exists($days, self::PERIODS) ? $days : 30;
    }

    /** @return list<array{date: Carbon, plays: int}> */
    private function daily($plays, Carbon $from, int $days): array
    {
        $counts = $plays->selectRaw('DATE(plays.created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $from->copy()->addDays($i);
            $series[] = ['date' => $date, 'plays' => (int) ($counts[$date->toDateString()] ?? 0)];
        }

        return $series;
    }

    /**
     * Curva de retención: qué porcentaje de las escuchas llega a cada punto del episodio (cada 5 %).
     *
     * @return list<array{percent: int, share: float}>|null
     */
    private function retention(Audio $audio, Carbon $from): ?array
    {
        if (! $audio->duration) {
            return null;
        }

        $selects = [];
        foreach (range(0, 100, 5) as $p) {
            $threshold = (int) floor($audio->duration * $p / 100);
            $selects[] = "SUM(CASE WHEN max_position >= {$threshold} THEN 1 ELSE 0 END) as p{$p}";
        }

        $row = Play::where('audio_id', $audio->id)
            ->where('created_at', '>=', $from)
            ->selectRaw('COUNT(*) as total, '.implode(', ', $selects))
            ->first();

        if (! $row || ! $row->total) {
            return null;
        }

        return array_map(fn ($p) => [
            'percent' => $p,
            'share' => (float) $row->{"p{$p}"} / (int) $row->total,
        ], range(0, 100, 5));
    }
}
