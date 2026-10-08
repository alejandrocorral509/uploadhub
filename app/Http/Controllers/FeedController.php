<?php

namespace App\Http\Controllers;

use App\Models\Show;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

// Feed RSS de un programa, compatible con Apple Podcasts, Spotify y cualquier app de podcasts
class FeedController extends Controller
{
    public function __invoke(Show $show): Response
    {
        $show->load(['user', 'category']);

        // Solo episodios públicos con archivo propio (los de YouTube no se pueden incluir en un feed)
        $episodes = $show->episodes()
            ->public()
            ->whereNotNull('filepath')
            ->where('processing_status', '!=', 'failed')
            ->with(['user', 'show'])
            ->latest()
            ->limit(300)
            ->get()
            ->map(function ($audio) {
                $disk = $audio->fileDisk();
                $audio->setAttribute('file_size', $disk ? Storage::disk($disk)->size($audio->filepath) : 0);

                return $audio;
            });

        return response()
            ->view('feeds.show', ['show' => $show, 'episodes' => $episodes])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=900');
    }
}
