<?php

use App\Models\Audio;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('el login se bloquea tras 5 intentos fallidos', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post('/login', ['email' => $user->email, 'password' => "mala-{$i}"]);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('el login regenera la sesión', function () {
    $user = User::factory()->create();
    $this->startSession();
    $before = session()->getId();

    $this->post('/login', ['email' => $user->email, 'password' => 'password']);

    $this->assertAuthenticatedAs($user);
    expect(session()->getId())->not->toBe($before);
});

test('la contraseña debe tener letras y números', function () {
    $this->post('/register', [
        'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'soloLetras', 'password_confirmation' => 'soloLetras',
    ])->assertSessionHasErrors('password');
});

test('el registro tiene límite de peticiones', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/register', ['name' => "U{$i}", 'email' => "u{$i}@example.com", 'password' => 'Clave2026x', 'password_confirmation' => 'Clave2026x']);
    }

    $this->post('/register', ['name' => 'U6', 'email' => 'u6@example.com', 'password' => 'Clave2026x', 'password_confirmation' => 'Clave2026x'])
        ->assertStatus(429);
});

test('los datos sensibles del usuario nunca salen en JSON', function () {
    $json = User::factory()->create()->toArray();

    expect($json)->not->toHaveKeys(['password', 'remember_token', 'email', 'role_id', 'banned_at']);
});

test('el seeder no crea un administrador con contraseña conocida', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(0);
});

test('los audios se sirven solo por la ruta con permisos, no por /storage', function () {
    $audio = Audio::factory()->withFile()->private()->create();

    $this->get('/storage/'.$audio->filepath)->assertStatus(403);
});
