<?php

use App\Models\Audio;
use App\Models\PlaybackProgress;
use App\Models\User;

test('una escucha cuenta una vez aunque se le dé al play varias veces seguidas', function () {
    $audio = Audio::factory()->withFile()->create();
    $listener = User::factory()->create();

    $first = $this->actingAs($listener)->postJson(route('plays.store', $audio))->assertOk()->json();
    $second = $this->actingAs($listener)->postJson(route('plays.store', $audio))->assertOk()->json();

    expect($first['play'])->toBe($second['play'])
        ->and($audio->fresh()->plays_count)->toBe(1);

    $this->travel(31)->minutes();
    $this->actingAs($listener)->postJson(route('plays.store', $audio));
    expect($audio->fresh()->plays_count)->toBe(2);

    // Otra persona es otra escucha
    $this->actingAs(User::factory()->create())->postJson(route('plays.store', $audio));
    expect($audio->fresh()->plays_count)->toBe(3);
});

test('sin cuenta también se puede escuchar un episodio público', function () {
    $audio = Audio::factory()->withFile()->create();

    $this->postJson(route('plays.store', $audio))->assertOk()->assertJsonStructure(['play', 'update_url']);
    expect($audio->fresh()->plays_count)->toBe(1);
});

test('el progreso se guarda para continuar donde se dejó', function () {
    $audio = Audio::factory()->withFile()->create(['duration' => 300]);
    $user = User::factory()->create();

    $updateUrl = $this->actingAs($user)->postJson(route('plays.store', $audio))->json('update_url');
    $this->actingAs($user)->patchJson($updateUrl, ['position' => 120])->assertOk();
    $this->actingAs($user)->patchJson($updateUrl, ['position' => 90])->assertOk();

    $progress = PlaybackProgress::where('user_id', $user->id)->first();
    expect($progress->position)->toBe(90)
        ->and($progress->completed)->toBeFalse()
        ->and($audio->plays()->first()->max_position)->toBe(120);

    $this->actingAs($user)->patchJson($updateUrl, ['position' => 290]);
    expect($progress->fresh()->completed)->toBeTrue();

    // La página del episodio ofrece continuar solo si no está terminado
    $this->actingAs($user)->get($audio->url())->assertSee('Reproducir');
});

test('nadie puede actualizar la escucha de otra persona', function () {
    $audio = Audio::factory()->withFile()->create();
    $updateUrl = $this->actingAs(User::factory()->create())->postJson(route('plays.store', $audio))->json('update_url');

    $this->actingAs(User::factory()->create())->patchJson($updateUrl, ['position' => 10])->assertForbidden();
});

test('si falta la duración se toma la que informa el reproductor', function () {
    $audio = Audio::factory()->withFile()->create(['duration' => null]);
    $listener = User::factory()->create();
    $updateUrl = $this->actingAs($listener)->postJson(route('plays.store', $audio))->json('update_url');

    $this->actingAs($listener)->patchJson($updateUrl, ['position' => 10, 'duration' => 1234]);

    expect($audio->fresh()->duration)->toBe(1234);
});
