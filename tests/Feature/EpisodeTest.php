<?php

use App\Jobs\ProcessAudio;
use App\Models\Audio;
use App\Models\Show;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

function episodeData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Mi episodio',
        'description' => 'Descripción',
        'privacy' => 'public',
        'categories' => categoryIds(),
        'tags' => 'uno, dos, Uno',
    ], $overrides);
}

describe('crear', function () {
    test('subir un archivo lo guarda en el disco privado y lanza el procesado', function () {
        Bus::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('episodes.store'), episodeData([
                'audio' => UploadedFile::fake()->create('episodio.mp3', 200, 'audio/mpeg'),
            ]))
            ->assertRedirect();

        $audio = Audio::firstOrFail();
        Storage::disk('local')->assertExists($audio->filepath);
        Storage::disk('public')->assertMissing($audio->filepath);
        expect($audio->processing_status)->toBe(Audio::STATUS_PENDING)
            ->and($audio->tags()->count())->toBe(2) // "Uno" repetida no cuenta
            ->and($audio->user_id)->toBe($user->id);
        Bus::assertDispatched(ProcessAudio::class);
    });

    test('responde con JSON al formulario que envía por fetch', function () {
        Bus::fake();

        $this->actingAs(User::factory()->create())
            ->postJson(route('episodes.store'), episodeData(['youtube_url' => 'https://youtu.be/dQw4w9WgXcQ']))
            ->assertOk()
            ->assertJsonStructure(['redirect']);
    });

    test('admite cualquier formato de enlace de YouTube', function (string $url) {
        $this->actingAs(User::factory()->create())
            ->post(route('episodes.store'), episodeData(['youtube_url' => $url]))
            ->assertSessionHasNoErrors();

        expect(Audio::first()->youtubeId())->toBe('dQw4w9WgXcQ');
    })->with([
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtube.com/watch?v=dQw4w9WgXcQ&t=10',
        'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
        'https://youtu.be/dQw4w9WgXcQ',
        'https://www.youtube.com/shorts/dQw4w9WgXcQ',
        'youtube.com/embed/dQw4w9WgXcQ',
    ]);

    test('rechaza enlaces que no son de YouTube', function () {
        $this->actingAs(User::factory()->create())
            ->post(route('episodes.store'), episodeData(['youtube_url' => 'https://evil.example/watch?v=dQw4w9WgXcQ']))
            ->assertSessionHasErrors('youtube_url');
    });

    test('valida el origen, las categorías, las etiquetas y el programa', function () {
        $user = User::factory()->create();
        $otherShow = Show::factory()->create();

        $this->actingAs($user)->post(route('episodes.store'), episodeData())->assertSessionHasErrors('audio');

        $this->actingAs($user)->post(route('episodes.store'), episodeData([
            'audio' => UploadedFile::fake()->create('a.mp3', 10, 'audio/mpeg'),
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ]))->assertSessionHasErrors('audio');

        $this->actingAs($user)->post(route('episodes.store'), episodeData([
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'categories' => categoryIds(4),
            'tags' => str_repeat('a', 51),
            'show_id' => $otherShow->id,
        ]))->assertSessionHasErrors(['categories', 'tags', 'show_id']);

        expect(Audio::count())->toBe(0);
    });

    test('rechaza archivos que no son audio aunque tengan extensión de audio', function () {
        // UploadedFile real (no fake): el tipo se detecta por el contenido, como en una petición de verdad
        $path = tempnam(sys_get_temp_dir(), 'up');
        file_put_contents($path, '<html><body><script>alert(1)</script></body></html>');
        $file = new UploadedFile($path, 'falso.mp3', 'audio/mpeg', null, true);

        $this->actingAs(User::factory()->create())
            ->post(route('episodes.store'), episodeData(['audio' => $file]))
            ->assertSessionHasErrors('audio');

        expect(Audio::count())->toBe(0);
    });

    test('hay que iniciar sesión para subir', function () {
        $this->get(route('episodes.create'))->assertRedirect(route('login'));
        $this->post(route('episodes.store'), episodeData())->assertRedirect(route('login'));
    });
});

describe('editar y borrar', function () {
    test('solo el autor puede editar', function () {
        $audio = Audio::factory()->youtube()->create();

        $this->actingAs(User::factory()->create())->get(route('episodes.edit', $audio))->assertForbidden();
        $this->actingAs(User::factory()->create())->put(route('episodes.update', $audio), episodeData())->assertForbidden();
        $this->actingAs($audio->user)->get(route('episodes.edit', $audio))->assertOk();
    });

    test('cambiar el archivo borra el anterior y quitar la portada la borra', function () {
        Bus::fake();
        $audio = Audio::factory()->withFile()->create(['cover' => 'covers/vieja.jpg']);
        Storage::disk('public')->put('covers/vieja.jpg', 'img');
        $oldPath = $audio->filepath;

        $this->actingAs($audio->user)->put(route('episodes.update', $audio), episodeData([
            'audio' => UploadedFile::fake()->create('nuevo.mp3', 50, 'audio/mpeg'),
            'remove_cover' => 1,
        ]))->assertRedirect();

        $audio->refresh();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($audio->filepath);
        Storage::disk('public')->assertMissing('covers/vieja.jpg');
        expect($audio->cover)->toBeNull();
    });

    test('borrar un episodio borra su archivo y su portada', function () {
        $audio = Audio::factory()->withFile()->create(['cover' => 'covers/c.jpg']);
        Storage::disk('public')->put('covers/c.jpg', 'img');

        $this->actingAs(User::factory()->create())->delete(route('episodes.destroy', $audio))->assertForbidden();
        $this->actingAs($audio->user)->delete(route('episodes.destroy', $audio))->assertRedirect(route('library'));

        Storage::disk('local')->assertMissing($audio->filepath);
        Storage::disk('public')->assertMissing('covers/c.jpg');
        expect(Audio::count())->toBe(0);
    });

    test('al borrar una cuenta se borran los archivos de sus episodios', function () {
        $audio = Audio::factory()->withFile()->create();

        $this->actingAs($audio->user)->delete(route('profile.destroy'), ['password' => 'password'])->assertRedirect('/');

        Storage::disk('local')->assertMissing($audio->filepath);
    });
});

describe('privacidad', function () {
    test('la página de un episodio público la ve cualquiera y redirige a la URL con slug', function () {
        $audio = Audio::factory()->withFile()->create(['title' => 'Hola Mundo']);

        $this->get(route('episodes.show', $audio))->assertRedirect($audio->url());
        $this->get($audio->url())->assertOk()->assertSee('Hola Mundo');
    });

    test('un episodio privado ajeno responde 404 en todas sus rutas', function () {
        $audio = Audio::factory()->withFile()->private()->create();
        $other = User::factory()->create();

        foreach ([null, $other] as $viewer) {
            $request = $viewer ? $this->actingAs($viewer) : $this;
            $request->get($audio->url())->assertNotFound();
            $request->get(route('audios.stream', $audio))->assertNotFound();
            $request->get(route('audios.download', $audio))->assertNotFound();
            $request->post(route('plays.store', $audio))->assertNotFound();
        }

        $this->actingAs($other)->post(route('likes.toggle', $audio))->assertNotFound();
        $this->actingAs($other)->post(route('comments.store', $audio), ['content' => 'Hola'])->assertNotFound();

        $this->actingAs($audio->user)->get($audio->url())->assertOk();
        $this->actingAs($audio->user)->get(route('audios.stream', $audio))->assertOk();
    });

    test('explorar solo muestra episodios públicos', function () {
        Audio::factory()->youtube()->create(['title' => 'Episodio visible']);
        Audio::factory()->youtube()->private()->create(['title' => 'Borrador secreto']);

        $this->get(route('explore'))->assertOk()->assertSee('Episodio visible')->assertDontSee('Borrador secreto');
    });

    test('la búsqueda encuentra por etiqueta, autor y transcripción', function () {
        $audio = Audio::factory()->youtube()->create(['title' => 'Sin pistas', 'transcript' => 'hablamos de criptografía cuántica']);

        $this->get(route('explore', ['q' => 'cuántica']))->assertSee('Sin pistas');
        $this->get(route('explore', ['q' => $audio->user->name]))->assertSee('Sin pistas');
        $this->get(route('explore', ['q' => 'zzz-nada']))->assertDontSee('Sin pistas');
    });
});

describe('reproducción y descarga', function () {
    test('el stream admite saltar (Range) y no se fía del tipo guardado', function () {
        $audio = Audio::factory()->withFile(str_repeat('a', 1000))->create(['filetype' => 'text/html']);

        $response = $this->withHeader('Range', 'bytes=0-9')->get(route('audios.stream', $audio));

        $response->assertStatus(206)->assertHeader('X-Content-Type-Options', 'nosniff');
        expect($response->headers->get('Content-Type'))->not->toContain('html');
    });

    test('la descarga usa el título como nombre de archivo', function () {
        $audio = Audio::factory()->withFile()->create(['title' => 'Mi Gran Episodio']);

        $this->get(route('audios.download', $audio))
            ->assertOk()
            ->assertDownload('mi-gran-episodio.mp3');
    });

    test('los audios subidos con el TFG original (disco público) se siguen sirviendo', function () {
        Storage::disk('public')->put('audios/antiguo.mp3', 'audio');
        $audio = Audio::factory()->create(['filepath' => 'audios/antiguo.mp3']);

        $this->get(route('audios.stream', $audio))->assertOk();
    });
});

describe('me gusta y comentarios', function () {
    test('me gusta se activa y se desactiva', function () {
        $audio = Audio::factory()->youtube()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('likes.toggle', $audio))->assertJson(['liked' => true, 'likes_count' => 1]);
        $this->actingAs($user)->postJson(route('likes.toggle', $audio))->assertJson(['liked' => false, 'likes_count' => 0]);
    });

    test('pueden borrar un comentario su autor, el autor del episodio y un admin; nadie más', function () {
        $audio = Audio::factory()->youtube()->create();
        $writer = User::factory()->create();

        $comments = collect(range(1, 3))->map(fn () => $audio->comments()->create(['user_id' => $writer->id, 'content' => 'Hola']));

        $this->actingAs(User::factory()->create())->delete(route('comments.destroy', $comments[0]))->assertForbidden();
        $this->actingAs($writer)->delete(route('comments.destroy', $comments[0]))->assertRedirect();
        $this->actingAs($audio->user)->delete(route('comments.destroy', $comments[1]))->assertRedirect();
        $this->actingAs(admin())->delete(route('comments.destroy', $comments[2]))->assertRedirect();

        expect($audio->comments()->count())->toBe(0);
    });

    test('los comentarios tienen límite de peticiones', function () {
        $audio = Audio::factory()->youtube()->create();
        $user = User::factory()->create();

        foreach (range(1, 6) as $i) {
            $this->actingAs($user)->post(route('comments.store', $audio), ['content' => "Comentario {$i}"])->assertRedirect();
        }
        $this->actingAs($user)->post(route('comments.store', $audio), ['content' => 'Uno más'])->assertStatus(429);
    });
});
