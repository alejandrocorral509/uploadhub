<?php

use App\Http\Controllers\Api\V1\EpisodeController;
use App\Http\Controllers\Api\V1\ShowController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

// API REST para apps (por ejemplo, una app Android). Autenticación con tokens de Sanctum.
Route::prefix('v1')->name('api.')->middleware('throttle:api')->group(function () {
    Route::post('/tokens', [TokenController::class, 'store'])->middleware('throttle:api-tokens')->name('tokens.store');

    // Públicas (si se envía un token, también se ven los episodios privados propios)
    Route::get('/episodes', [EpisodeController::class, 'index'])->name('episodes.index');
    Route::get('/episodes/{audio}', [EpisodeController::class, 'show'])->name('episodes.show');
    Route::get('/episodes/{audio}/comments', [EpisodeController::class, 'comments'])->name('episodes.comments');
    Route::get('/shows', [ShowController::class, 'index'])->name('shows.index');
    Route::get('/shows/{show}', [ShowController::class, 'show'])->name('shows.show');
    Route::get('/shows/{show}/episodes', [ShowController::class, 'episodes'])->name('shows.episodes');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [TokenController::class, 'me'])->name('me');
        Route::delete('/tokens/current', [TokenController::class, 'destroy'])->name('tokens.destroy');
        Route::get('/me/feed', [EpisodeController::class, 'feed'])->name('feed');
        Route::post('/episodes/{audio}/like', [EpisodeController::class, 'like'])->name('episodes.like');
        Route::post('/episodes/{audio}/comments', [EpisodeController::class, 'comment'])->middleware('throttle:comments')->name('episodes.comment');
        Route::post('/shows/{show}/subscription', [ShowController::class, 'subscribe'])->name('shows.subscribe');
    });
});
