<?php

use App\Models\Audio;
use App\Models\Show;
use App\Models\User;

function token(User $user): string
{
    // En los tests, el guard recuerda el usuario de la petición anterior: se olvida para usar el token nuevo
    app('auth')->forgetGuards();

    return test()->postJson(route('api.tokens.store'), [
        'email' => $user->email, 'password' => 'password', 'device_name' => 'Android',
    ])->assertCreated()->json('token');
}

// Petición con token. En los tests la app se reutiliza y Sanctum recordaría el usuario anterior.
function withApiToken(string $token)
{
    app('auth')->forgetGuards();

    return test()->withToken($token);
}

test('se obtiene un token con correo y contraseña', function () {
    $user = User::factory()->create();

    $this->postJson(route('api.tokens.store'), ['email' => $user->email, 'password' => 'mal', 'device_name' => 'x'])
        ->assertJsonValidationErrors('email');

    $token = token($user);
    $this->withToken($token)->getJson(route('api.me'))->assertOk()->assertJsonPath('data.email', $user->email);
    $this->withToken($token)->deleteJson(route('api.tokens.destroy'))->assertNoContent();
});

test('una cuenta bloqueada no obtiene token', function () {
    $user = User::factory()->create(['banned_at' => now()]);

    $this->postJson(route('api.tokens.store'), ['email' => $user->email, 'password' => 'password', 'device_name' => 'x'])
        ->assertJsonValidationErrors('email');
});

test('el listado de episodios es público y no incluye privados', function () {
    Audio::factory()->withFile()->create(['title' => 'Visible']);
    Audio::factory()->withFile()->private()->create(['title' => 'Oculto']);

    $this->getJson(route('api.episodes.index'))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Visible')
        ->assertJsonStructure(['data' => [['id', 'title', 'stream_url', 'author' => ['name', 'url'], 'url']], 'links', 'meta'])
        ->assertJsonMissingPath('data.0.author.email');
});

test('un episodio privado solo lo ve su autor con su token', function () {
    $audio = Audio::factory()->withFile()->private()->create();

    $this->getJson(route('api.episodes.show', $audio))->assertNotFound();
    withApiToken(token(User::factory()->create()))->getJson(route('api.episodes.show', $audio))->assertNotFound();
    withApiToken(token($audio->user))->getJson(route('api.episodes.show', $audio))->assertOk();
});

test('me gusta, comentarios y suscripciones desde la API', function () {
    $show = Show::factory()->create();
    $audio = Audio::factory()->withFile()->create(['user_id' => $show->user_id, 'show_id' => $show->id]);
    $token = token(User::factory()->create());

    $this->postJson(route('api.episodes.like', $audio))->assertUnauthorized();
    $this->withToken($token)->postJson(route('api.episodes.like', $audio))->assertJson(['liked' => true]);
    $this->withToken($token)->postJson(route('api.episodes.comment', $audio), ['content' => 'Desde la app'])
        ->assertCreated()->assertJsonPath('data.content', 'Desde la app');
    $this->getJson(route('api.episodes.comments', $audio))->assertJsonCount(1, 'data');

    $this->withToken($token)->postJson(route('api.shows.subscribe', $show))->assertJson(['subscribed' => true]);
    $this->withToken($token)->getJson(route('api.feed'))->assertJsonCount(1, 'data');
});

test('programas en la API', function () {
    $show = Show::factory()->create();
    Audio::factory()->withFile()->create(['user_id' => $show->user_id, 'show_id' => $show->id]);

    $this->getJson(route('api.shows.index'))->assertOk()->assertJsonPath('data.0.slug', $show->slug)->assertJsonPath('data.0.episodes_count', 1);
    $this->getJson(route('api.shows.show', $show))->assertOk()->assertJsonPath('data.feed_url', $show->feedUrl());
    $this->getJson(route('api.shows.episodes', $show))->assertOk()->assertJsonCount(1, 'data');
});
