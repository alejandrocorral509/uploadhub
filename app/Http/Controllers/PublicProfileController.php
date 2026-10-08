<?php

namespace App\Http\Controllers;

use App\Models\PlaybackProgress;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProfileController extends Controller
{
    public function __invoke(Request $request, User $user): View
    {
        abort_if($user->isBanned(), 404);

        $episodes = $user->audios()
            ->visibleTo($request->user())
            ->with(['user', 'show'])
            ->withCount('likes')
            ->latest()
            ->paginate(12);

        return view('profiles.show', [
            'profile' => $user,
            'shows' => $user->shows()->withCount(['episodes' => fn ($q) => $q->public(), 'subscribers'])->latest()->get(),
            'episodes' => $episodes,
            'stats' => [
                'episodes' => $user->audios()->public()->count(),
                'plays' => (int) $user->audios()->public()->sum('plays_count'),
            ],
            'positions' => PlaybackProgress::positionsFor($request->user(), $episodes),
        ]);
    }
}
