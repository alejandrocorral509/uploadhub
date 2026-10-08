<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audio;
use App\Models\Comment;
use App\Models\Play;
use App\Models\Report;
use App\Models\Show;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'counts' => [
                'users' => User::count(),
                'banned' => User::whereNotNull('banned_at')->count(),
                'episodes' => Audio::count(),
                'shows' => Show::count(),
                'comments' => Comment::count(),
                'plays_week' => Play::where('created_at', '>=', now()->subDays(7))->count(),
                'open_reports' => Report::where('status', Report::STATUS_OPEN)->count(),
                'failed' => Audio::where('processing_status', Audio::STATUS_FAILED)->count(),
            ],
            'reports' => Report::withReportable()->where('status', Report::STATUS_OPEN)->latest()->limit(5)->get(),
            'newUsers' => User::latest()->limit(5)->get(),
        ]);
    }
}
