<?php

use App\Models\Audio;
use App\Models\Report;
use App\Models\User;

test('el panel solo es para administradores', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))->assertForbidden();

    $admin = admin();
    foreach (['admin.dashboard', 'admin.reports.index', 'admin.users.index', 'admin.episodes.index', 'admin.comments.index'] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

test('denunciar y eliminar el contenido cierra todas sus denuncias', function () {
    $audio = Audio::factory()->withFile()->create();
    [$a, $b] = User::factory()->count(2)->create();

    $this->actingAs($a)->post(route('reports.store'), ['type' => 'audio', 'id' => $audio->id, 'reason' => 'spam'])->assertRedirect();
    $this->actingAs($b)->post(route('reports.store'), ['type' => 'audio', 'id' => $audio->id, 'reason' => 'offensive'])->assertRedirect();
    // Denunciar dos veces lo mismo no duplica
    $this->actingAs($a)->post(route('reports.store'), ['type' => 'audio', 'id' => $audio->id, 'reason' => 'spam']);
    expect(Report::count())->toBe(2);

    $admin = admin();
    $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk()->assertSee($audio->title);
    $this->actingAs($admin)->patch(route('admin.reports.update', Report::first()), ['action' => 'remove'])->assertRedirect();

    expect(Audio::count())->toBe(0)
        ->and(Report::where('status', 'open')->count())->toBe(0);
});

test('no se puede denunciar un episodio privado ajeno', function () {
    $audio = Audio::factory()->youtube()->private()->create();

    $this->actingAs(User::factory()->create())
        ->post(route('reports.store'), ['type' => 'audio', 'id' => $audio->id, 'reason' => 'spam'])
        ->assertNotFound();
});

test('un usuario bloqueado no puede entrar y se le cierra la sesión abierta', function () {
    $user = User::factory()->create();
    $admin = admin();

    $this->actingAs($admin)->patch(route('admin.users.ban', $user))->assertRedirect();
    expect($user->fresh()->isBanned())->toBeTrue();

    // Con sesión abierta: se le echa en la siguiente petición
    $this->actingAs($user->fresh())->get(route('library'))->assertRedirect(route('login'));
    $this->assertGuest();

    // Iniciar sesión de nuevo: rechazado
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
    $this->assertGuest();

    // Su perfil público deja de verse
    $this->get($user->profileUrl())->assertNotFound();
});

test('un administrador no puede bloquearse ni quitarse el rol a sí mismo', function () {
    $admin = admin();

    $this->actingAs($admin)->patch(route('admin.users.ban', $admin))->assertStatus(422);
    $this->actingAs($admin)->patch(route('admin.users.admin', $admin))->assertStatus(422);
});

test('dar y quitar el rol de administrador', function () {
    $user = User::factory()->create();
    $this->actingAs(admin())->patch(route('admin.users.admin', $user));
    expect($user->fresh()->isAdmin())->toBeTrue();
});

test('un administrador puede borrar episodios ajenos', function () {
    $audio = Audio::factory()->withFile()->create();

    $this->actingAs(admin())->from(route('admin.episodes.index'))->delete(route('episodes.destroy', $audio))
        ->assertRedirect(route('admin.episodes.index'));

    expect(Audio::count())->toBe(0);
});

test('el comando user:make-admin', function () {
    $user = User::factory()->create(['email' => 'jefa@example.com']);

    $this->artisan('user:make-admin', ['email' => 'jefa@example.com'])->assertSuccessful();
    $this->artisan('user:make-admin', ['email' => 'nadie@example.com'])->assertFailed();

    expect($user->fresh()->isAdmin())->toBeTrue();
});
