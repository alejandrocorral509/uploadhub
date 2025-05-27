<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AudioController;
use App\Http\Controllers\HomeController;



// Rutas de autenticación (serán gestionadas por 'auth.php')
require __DIR__.'/auth.php';

// Rutas de inicio de sesión
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);

// Rutas de registro
Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);

// Rutas protegidas por autenticación
Route::middleware(['auth'])->group(function () {
    // Rutas de ProfileController
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Rutas públicas
Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [AudioController::class, 'index'])->name('dashboard');
    Route::post('/audios', [AudioController::class, 'store'])->name('audios.store');
    Route::delete('/audios/{audio}', [AudioController::class, 'destroy'])->name('audios.destroy');
});

Route::get('/audios-publicos', [AudioController::class, 'publicIndex'])->name('audios.public');

use App\Http\Controllers\CommentController;

Route::post('/audios/{audio}/comments', [CommentController::class, 'store'])
    ->middleware('auth')
    ->name('comments.store');


 Route::post('/audios/{audio}/like', [AudioController::class, 'toggleLike'])->middleware('auth')->name('audios.like');

Route::get('/audios/create', [AudioController::class, 'create'])->name('audios.create');
Route::post('/audios', [AudioController::class, 'store'])->name('audios.store');

Route::get('/audios/upload', [AudioController::class, 'upload'])->name('audios.upload');

Route::post('/audios/{audio}/toggle-like', [AudioController::class, 'toggleLike'])->middleware('auth')->name('audios.toggle-like');



Route::get('/audios/{audio}/download', [AudioController::class, 'download'])->name('audios.download');

