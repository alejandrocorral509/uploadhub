<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\FeedController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PlayController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

// --- Público -----------------------------------------------------------------

Route::get('/', HomeController::class)->name('home');
Route::post('/demo/entrar', [DemoController::class, 'login'])->middleware(['guest', 'throttle:register'])->name('demo.login');
Route::get('/explorar', ExploreController::class)->name('explore');
Route::redirect('/audios-publicos', '/explorar', 301);

Route::get('/programas', [ShowController::class, 'index'])->name('shows.index');
Route::get('/u/{user}', PublicProfileController::class)->name('profiles.show');

// Reproducción y descarga: públicas para episodios públicos; la política comprueba los privados
Route::get('/episodios/{audio}/stream', [EpisodeController::class, 'stream'])->whereNumber('audio')->name('audios.stream');
Route::get('/episodios/{audio}/descargar', [EpisodeController::class, 'download'])->whereNumber('audio')->name('audios.download');

Route::post('/episodios/{audio}/escuchas', [PlayController::class, 'store'])->whereNumber('audio')->middleware('throttle:plays')->name('plays.store');
Route::patch('/escuchas/{play}', [PlayController::class, 'update'])->middleware('throttle:plays')->name('plays.update');

// --- Con sesión iniciada -------------------------------------------------------

Route::middleware('auth')->group(function () {
    Route::get('/biblioteca', LibraryController::class)->name('library');
    Route::redirect('/dashboard', '/biblioteca')->name('dashboard');

    Route::get('/estadisticas', [StatsController::class, 'index'])->name('stats.index');
    Route::get('/estadisticas/episodios/{audio}', [StatsController::class, 'episode'])->name('stats.episode');

    // Ajustes de la cuenta
    Route::get('/ajustes', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/ajustes', [ProfileController::class, 'update'])->middleware('demo.protect')->name('profile.update');
    Route::delete('/ajustes', [ProfileController::class, 'destroy'])->middleware('demo.protect')->name('profile.destroy');
    Route::redirect('/profile', '/ajustes');

    // Episodios
    Route::get('/subir', [EpisodeController::class, 'create'])->name('episodes.create');
    Route::redirect('/audios/upload', '/subir');
    Route::post('/episodios', [EpisodeController::class, 'store'])->name('episodes.store');
    Route::get('/episodios/{audio}/editar', [EpisodeController::class, 'edit'])->name('episodes.edit');
    Route::put('/episodios/{audio}', [EpisodeController::class, 'update'])->name('episodes.update');
    Route::delete('/episodios/{audio}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');

    // Subida por trozos
    Route::middleware('throttle:uploads')->group(function () {
        Route::post('/subidas', [UploadController::class, 'start'])->name('uploads.start');
        Route::post('/subidas/{token}', [UploadController::class, 'chunk'])->whereUuid('token')->name('uploads.chunk');
        Route::delete('/subidas/{token}', [UploadController::class, 'cancel'])->whereUuid('token')->name('uploads.cancel');
    });

    Route::post('/episodios/{audio}/me-gusta', LikeController::class)->middleware('throttle:likes')->name('likes.toggle');
    Route::post('/episodios/{audio}/comentarios', [CommentController::class, 'store'])->middleware('throttle:comments')->name('comments.store');
    Route::delete('/comentarios/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/denuncias', [ReportController::class, 'store'])->middleware('throttle:reports')->name('reports.store');

    // Programas
    Route::get('/programas/crear', [ShowController::class, 'create'])->name('shows.create');
    Route::post('/programas', [ShowController::class, 'store'])->name('shows.store');
    Route::get('/programas/{show}/editar', [ShowController::class, 'edit'])->name('shows.edit');
    Route::put('/programas/{show}', [ShowController::class, 'update'])->name('shows.update');
    Route::delete('/programas/{show}', [ShowController::class, 'destroy'])->name('shows.destroy');
    Route::post('/programas/{show}/suscripcion', SubscriptionController::class)->middleware('throttle:likes')->name('shows.subscribe');
});

// --- Administración ------------------------------------------------------------

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', Admin\DashboardController::class)->name('dashboard');
    Route::get('/denuncias', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::patch('/denuncias/{report}', [Admin\ReportController::class, 'update'])->name('reports.update');
    Route::get('/usuarios', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('/usuarios/{user}/bloqueo', [Admin\UserController::class, 'ban'])->name('users.ban');
    Route::patch('/usuarios/{user}/admin', [Admin\UserController::class, 'toggleAdmin'])->name('users.admin');
    Route::get('/episodios', [Admin\ContentController::class, 'episodes'])->name('episodes.index');
    Route::get('/comentarios', [Admin\ContentController::class, 'comments'])->name('comments.index');
});

// Estas van al final para que /programas/crear no se tome por un programa llamado "crear"
Route::get('/programas/{show}', [ShowController::class, 'show'])->name('shows.show');
Route::get('/programas/{show}/feed.xml', FeedController::class)->name('shows.feed');
Route::get('/episodios/{audio}/{slug?}', [EpisodeController::class, 'show'])->whereNumber('audio')->name('episodes.show');
