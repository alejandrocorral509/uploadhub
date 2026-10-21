<?php

use App\Models\Audio;
use App\Models\Show;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

test('sin modo demo no hay botón ni ruta de demo', function () {
    $this->get(route('home'))->assertDontSee('Demo de portfolio');
    $this->post(route('demo.login'))->assertNotFound();
});

describe('con modo demo', function () {
    beforeEach(function () {
        config(['demo.enabled' => true]);
        $this->seed(DemoSeeder::class);
    });

    test('se puede entrar con la cuenta de ejemplo sin registrarse', function () {
        $this->get(route('home'))->assertSee('Demo de portfolio')->assertSee('Entrar como creadora de ejemplo');

        $this->post(route('demo.login'))->assertRedirect(route('library'));

        $this->assertAuthenticatedAs(User::where('email', 'laura@demo.test')->first());
    });

    test('las cuentas de ejemplo no se pueden modificar ni borrar', function () {
        $laura = User::where('email', 'laura@demo.test')->first();
        $this->actingAs($laura);

        $this->patch(route('profile.update'), ['name' => 'Hackeada', 'email' => 'otro@example.com'])->assertRedirect();
        $this->put(route('password.update'), ['current_password' => 'x', 'password' => 'Nueva2026x', 'password_confirmation' => 'Nueva2026x'])->assertRedirect();
        $this->delete(route('profile.destroy'), ['password' => 'demo-uploadhub'])->assertRedirect();

        expect($laura->fresh())->not->toBeNull()
            ->and($laura->fresh()->name)->toBe('Laura Gómez')
            ->and($laura->fresh()->email)->toBe('laura@demo.test');
    });

    test('las cuentas propias de los visitantes sí se pueden modificar', function () {
        $visitor = User::factory()->create();

        $this->actingAs($visitor)->patch(route('profile.update'), ['name' => 'Nuevo', 'email' => $visitor->email])->assertRedirect(route('profile.edit'));

        expect($visitor->fresh()->name)->toBe('Nuevo');
    });

    test('cada cuenta puede crear un número limitado de episodios y programas al día', function () {
        Bus::fake();
        config(['demo.max_episodes_per_day' => 2]);
        $visitor = User::factory()->create();
        $data = fn ($i) => ['title' => "Ep {$i}", 'privacy' => 'public', 'categories' => categoryIds(), 'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ'];

        $this->actingAs($visitor)->post(route('episodes.store'), $data(1))->assertSessionHasNoErrors();
        $this->actingAs($visitor)->post(route('episodes.store'), $data(2))->assertSessionHasNoErrors();
        $this->actingAs($visitor)->post(route('episodes.store'), $data(3))->assertSessionHasErrors('audio');
        $this->actingAs($visitor)->postJson(route('uploads.start'), ['filename' => 'a.mp3', 'size' => 10])->assertJsonValidationErrors('file');

        $this->actingAs($visitor)->post(route('shows.store'), ['title' => 'Uno', 'language' => 'es'])->assertSessionHasNoErrors();
        $this->actingAs($visitor)->post(route('shows.store'), ['title' => 'Dos', 'language' => 'es'])->assertSessionHasErrors('title');

        $this->travel(25)->hours();
        $this->actingAs($visitor)->post(route('episodes.store'), $data(4))->assertSessionHasNoErrors();
    });

    test('demo:reset borra lo de los visitantes y restaura los datos de ejemplo', function () {
        $visitor = User::factory()->create();
        $audio = Audio::factory()->withFile()->create(['user_id' => $visitor->id]);
        Show::where('slug', 'codigo-y-cafe')->first()->delete();

        $this->artisan('demo:reset')->assertSuccessful();

        expect(User::where('email', $visitor->email)->exists())->toBeFalse()
            ->and(Show::where('slug', 'codigo-y-cafe')->exists())->toBeTrue()
            ->and(User::where('email', 'like', '%@demo.test')->count())->toBe(4);
        Storage::disk('local')->assertMissing($audio->filepath);
    });
});

test('demo:reset no hace nada si el modo demo está apagado', function () {
    $user = User::factory()->create();

    $this->artisan('demo:reset')->assertFailed();

    expect($user->fresh())->not->toBeNull();
});
