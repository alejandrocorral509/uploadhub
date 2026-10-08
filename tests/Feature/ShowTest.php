<?php

use App\Models\Audio;
use App\Models\Show;
use App\Models\User;

test('crear un programa le da un slug único', function () {
    $user = User::factory()->create();
    Show::factory()->create(['title' => 'Código y café']);

    $this->actingAs($user)->post(route('shows.store'), [
        'title' => 'Código y café',
        'language' => 'es',
        'cover' => fixtureImage('cover-600.jpg'),
    ])->assertRedirect(route('shows.show', 'codigo-y-cafe-2'));

    $show = Show::where('slug', 'codigo-y-cafe-2')->firstOrFail();
    expect($show->user_id)->toBe($user->id)->and($show->cover)->not->toBeNull();
});

test('la portada del programa debe medir al menos 300 px', function () {
    $this->actingAs(User::factory()->create())->post(route('shows.store'), [
        'title' => 'Pequeña',
        'language' => 'es',
        'cover' => fixtureImage('cover-100.jpg'),
    ])->assertSessionHasErrors('cover');
});

test('solo el autor edita o borra; al borrar, los episodios quedan sueltos', function () {
    $show = Show::factory()->create();
    $episode = Audio::factory()->youtube()->create(['user_id' => $show->user_id, 'show_id' => $show->id]);

    $this->actingAs(User::factory()->create())->get(route('shows.edit', $show))->assertForbidden();
    $this->actingAs(User::factory()->create())->delete(route('shows.destroy', $show))->assertForbidden();

    $this->actingAs($show->user)->put(route('shows.update', $show), ['title' => 'Nuevo nombre', 'language' => 'es'])
        ->assertRedirect(route('shows.show', 'nuevo-nombre'));
    $this->actingAs($show->user)->delete(route('shows.destroy', $show->fresh()))->assertRedirect();

    expect(Show::count())->toBe(0)->and($episode->fresh()->show_id)->toBeNull();
});

test('suscribirse y cancelar la suscripción', function () {
    $show = Show::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('shows.subscribe', $show))->assertJson(['subscribed' => true, 'subscribers_count' => 1]);
    $this->actingAs($user)->postJson(route('shows.subscribe', $show))->assertJson(['subscribed' => false, 'subscribers_count' => 0]);
});

test('los episodios de los programas suscritos aparecen en la biblioteca', function () {
    $show = Show::factory()->create();
    Audio::factory()->youtube()->create(['user_id' => $show->user_id, 'show_id' => $show->id, 'title' => 'Novedad del programa']);
    $user = User::factory()->create();
    $user->subscriptions()->attach($show);

    $this->actingAs($user)->get(route('library', ['tab' => 'suscripciones']))->assertOk()->assertSee('Novedad del programa');
});

test('el feed RSS es XML válido y solo incluye episodios públicos con archivo', function () {
    $show = Show::factory()->create(['title' => 'Mi programa & amigos', 'explicit' => true]);
    $attrs = ['user_id' => $show->user_id, 'show_id' => $show->id];
    Audio::factory()->withFile('audio-publico')->create($attrs + ['title' => 'Público', 'season' => 1, 'episode_number' => 3]);
    Audio::factory()->withFile()->private()->create($attrs + ['title' => 'Privado']);
    Audio::factory()->youtube()->create($attrs + ['title' => 'De YouTube']);

    $response = $this->get(route('shows.feed', $show))->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

    $xml = simplexml_load_string($response->getContent());
    expect($xml)->not->toBeFalse()
        ->and((string) $xml->channel->title)->toBe('Mi programa & amigos')
        ->and($xml->channel->item)->toHaveCount(1)
        ->and((string) $xml->channel->item[0]->title)->toBe('Público')
        ->and((int) $xml->channel->item[0]->enclosure['length'])->toBe(strlen('audio-publico'));

    $itunes = $xml->channel->item[0]->children('http://www.itunes.com/dtds/podcast-1.0.dtd');
    expect((string) $itunes->episode)->toBe('3')->and((string) $itunes->season)->toBe('1');
});
