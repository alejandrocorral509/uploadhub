<?php

use App\Http\Controllers\LibraryController;
use App\Models\Audio;
use App\Models\Report;
use App\Models\Show;
use App\Models\User;
use Database\Seeders\DemoSeeder;

// Recorre todas las páginas con los datos de demostración. Como la carga perezosa de relaciones
// lanza un error fuera de producción, este test también detecta cualquier problema N+1 en las vistas.
beforeEach(function () {
    $this->seed(DemoSeeder::class);
});

test('las páginas públicas cargan', function () {
    $episode = Audio::public()->whereNotNull('chapters')->firstOrFail();
    $show = Show::firstOrFail();

    foreach ([
        route('home'), route('explore'), route('explore', ['q' => 'laravel', 'orden' => 'escuchados']),
        route('shows.index'), route('shows.show', $show), route('shows.feed', $show),
        route('profiles.show', $show->user), $episode->url(), route('login'), route('register'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('las páginas de una cuenta cargan', function () {
    $user = User::where('email', 'laura@demo.test')->firstOrFail();
    $this->actingAs($user);
    $episode = $user->audios()->firstOrFail();
    $show = $user->shows()->firstOrFail();

    foreach (array_keys(LibraryController::TABS) as $tab) {
        $this->get(route('library', ['tab' => $tab]))->assertOk();
    }

    foreach ([
        route('library', ['orden' => 'plays_count', 'dir' => 'asc', 'q' => 'laravel']),
        route('episodes.create'), route('episodes.edit', $episode), route('shows.create'), route('shows.edit', $show),
        route('stats.index'), route('stats.index', ['dias' => 365]), route('stats.episode', $episode),
        route('profile.edit'), $episode->url(), route('home'),
    ] as $url) {
        $this->get($url)->assertOk();
    }
});

test('el panel de administración carga con denuncias de todos los tipos', function () {
    $pablo = User::where('email', 'pablo@demo.test')->firstOrFail();
    $audio = Audio::public()->firstOrFail();
    foreach ([['audio', $audio->id], ['show', Show::first()->id], ['comment', $audio->comments()->firstOr(fn () => $audio->comments()->create(['user_id' => $pablo->id, 'content' => 'x']))->id]] as [$type, $id]) {
        Report::create(['user_id' => $pablo->id, 'reportable_type' => $type, 'reportable_id' => $id, 'reason' => 'spam']);
    }

    $this->actingAs(admin());
    foreach (['admin.dashboard', 'admin.reports.index', 'admin.users.index', 'admin.episodes.index', 'admin.comments.index'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

test('la demo se puede volver a ejecutar sin duplicar datos', function () {
    $this->seed(DemoSeeder::class);

    expect(User::where('email', 'like', '%@demo.test')->count())->toBe(4)
        ->and(Show::count())->toBe(3);
});
