<?php

use App\Models\Audio;
use App\Models\Play;
use App\Models\User;

function playsFor(Audio $audio, array $positions): void
{
    foreach ($positions as $i => $position) {
        Play::create(['audio_id' => $audio->id, 'listener_hash' => "oyente-{$i}", 'max_position' => $position]);
    }
}

test('las estadísticas suman escuchas, oyentes y cuánto se escucha', function () {
    $audio = Audio::factory()->withFile()->create(['duration' => 100]);
    playsFor($audio, [100, 50, 25, 25]);
    Play::create(['audio_id' => $audio->id, 'listener_hash' => 'oyente-0', 'max_position' => 10]);

    $response = $this->actingAs($audio->user)->get(route('stats.index'))->assertOk();

    $totals = $response->viewData('totals');
    expect($totals['plays'])->toBe(5)
        ->and($totals['listeners'])->toBe(4)
        ->and(round($totals['completion'], 2))->toBe(0.42); // (1 + .5 + .25 + .25 + .1) / 5
});

test('la curva de retención indica qué parte llega a cada punto', function () {
    $audio = Audio::factory()->withFile()->create(['duration' => 100]);
    playsFor($audio, [100, 60, 30, 10]);

    $retention = collect($this->actingAs($audio->user)->get(route('stats.episode', $audio))->assertOk()->viewData('retention'))
        ->keyBy('percent');

    expect($retention[0]['share'])->toBe(1.0)
        ->and($retention[50]['share'])->toBe(0.5)
        ->and($retention[100]['share'])->toBe(0.25);
});

test('las estadísticas de un episodio solo las ve su autor', function () {
    $audio = Audio::factory()->withFile()->create();

    $this->get(route('stats.index'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('stats.episode', $audio))->assertForbidden();
});

test('las escuchas antiguas quedan fuera del periodo elegido', function () {
    $audio = Audio::factory()->withFile()->create();
    playsFor($audio, [10]);
    Play::query()->update(['created_at' => now()->subDays(20)]);

    expect($this->actingAs($audio->user)->get(route('stats.index', ['dias' => 7]))->viewData('totals')['plays'])->toBe(0)
        ->and($this->actingAs($audio->user)->get(route('stats.index', ['dias' => 30]))->viewData('totals')['plays'])->toBe(1);
});
