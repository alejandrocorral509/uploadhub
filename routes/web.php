<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;

// Rutas de autenticación (serán gestionadas por 'auth.php')
require __DIR__.'/auth.php';

// Rutas de inicio de sesión
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Rutas de registro
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
});

Route::get('/audios-publicos', [AudioController::class, 'publicIndex'])->name('audios.public');

Route::get('/audios/{audio}/download', [AudioController::class, 'download'])->name('audios.download');

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard y audios
    Route::get('/dashboard', [AudioController::class, 'index'])->name('dashboard');

    Route::post('/audios', [AudioController::class, 'store'])->name('audios.store');
    Route::delete('/audios/{audio}', [AudioController::class, 'destroy'])->name('audios.destroy');

    Route::get('/audios/create', [AudioController::class, 'create'])->name('audios.create');
    Route::get('/audios/upload', [AudioController::class, 'upload'])->name('audios.upload');

    // Comentarios
    Route::post('/audios/{audio}/comments', [CommentController::class, 'store'])->name('comments.store');

    // Likes toggle
    Route::post('/audios/{audio}/toggle-like', [AudioController::class, 'toggleLike'])->name('audios.toggle-like');

    Route::get('/audios/{audio}/edit', [AudioController::class, 'edit'])->name('audios.edit');
    Route::put('/audios/{audio}', [AudioController::class, 'update'])->name('audios.update');

});
