<?php

use App\Models\Audio;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

// Un MP3 mínimo válido (cabecera de trama MPEG) para que la detección por contenido lo reconozca
function fakeMp3(int $bytes): string
{
    $frame = "\xFF\xFB\x90\x64".str_repeat("\x00", 413);

    return substr(str_repeat($frame, (int) ceil($bytes / strlen($frame))), 0, $bytes);
}

function chunk(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('chunk', $content);
}

test('un archivo se sube en varios trozos y se publica como episodio', function () {
    Bus::fake();
    config(['media.chunk_mb' => 1]);
    $user = User::factory()->create();
    $content = fakeMp3(2_500_000);

    $start = $this->actingAs($user)->postJson(route('uploads.start'), ['filename' => 'largo.mp3', 'size' => strlen($content)])
        ->assertOk()->json();

    $chunkSize = $start['chunk_size'];
    for ($offset = 0; $offset < strlen($content); $offset += $chunkSize) {
        $response = $this->actingAs($user)->post(route('uploads.chunk', $start['token']), [
            'offset' => $offset,
            'chunk' => chunk(substr($content, $offset, $chunkSize)),
        ])->assertOk();
    }
    expect($response->json('complete'))->toBeTrue();

    $this->actingAs($user)->postJson(route('episodes.store'), [
        'title' => 'Subido por trozos',
        'privacy' => 'public',
        'categories' => categoryIds(),
        'upload_token' => $start['token'],
    ])->assertOk();

    $audio = Audio::firstOrFail();
    expect(Storage::disk('local')->get($audio->filepath))->toBe($content)
        ->and($audio->filetype)->toBe('audio/mpeg');
    Storage::disk('local')->assertMissing('chunks/'.$start['token'].'.part');
});

test('un trozo repetido no se añade dos veces (reintentos)', function () {
    $user = User::factory()->create();
    $start = $this->actingAs($user)->postJson(route('uploads.start'), ['filename' => 'a.mp3', 'size' => 10])->json();

    $this->actingAs($user)->post(route('uploads.chunk', $start['token']), ['offset' => 0, 'chunk' => chunk('12345')])->assertJson(['received' => 5]);
    $this->actingAs($user)->post(route('uploads.chunk', $start['token']), ['offset' => 0, 'chunk' => chunk('12345')])->assertJson(['received' => 5]);
    $this->actingAs($user)->post(route('uploads.chunk', $start['token']), ['offset' => 5, 'chunk' => chunk('67890')])->assertJson(['received' => 10, 'complete' => true]);
});

test('nadie más puede añadir trozos, cancelar ni usar la subida de otro usuario', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $start = $this->actingAs($owner)->postJson(route('uploads.start'), ['filename' => 'a.mp3', 'size' => 5])->json();
    $this->actingAs($owner)->post(route('uploads.chunk', $start['token']), ['offset' => 0, 'chunk' => chunk('12345')]);

    $this->actingAs($intruder)->post(route('uploads.chunk', $start['token']), ['offset' => 5, 'chunk' => chunk('x')])->assertNotFound();
    $this->actingAs($intruder)->delete(route('uploads.cancel', $start['token']))->assertNotFound();
    $this->actingAs($intruder)->post(route('episodes.store'), [
        'title' => 'Robado', 'privacy' => 'public', 'categories' => categoryIds(), 'upload_token' => $start['token'],
    ])->assertSessionHasErrors('audio');

    Storage::disk('local')->assertExists('chunks/'.$start['token'].'.part');
});

test('se rechazan formatos no admitidos y tamaños excesivos', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson(route('uploads.start'), ['filename' => 'virus.exe', 'size' => 10])->assertJsonValidationErrors('file');
    $this->actingAs($user)->postJson(route('uploads.start'), ['filename' => 'enorme.mp3', 'size' => 600 * 1024 * 1024])->assertJsonValidationErrors('file');
});

test('al publicar se comprueba el contenido real, no la extensión', function () {
    $user = User::factory()->create();
    $content = '<html>no soy un audio</html>';
    $start = $this->actingAs($user)->postJson(route('uploads.start'), ['filename' => 'falso.mp3', 'size' => strlen($content)])->json();
    $this->actingAs($user)->post(route('uploads.chunk', $start['token']), ['offset' => 0, 'chunk' => chunk($content)]);

    $this->actingAs($user)->postJson(route('episodes.store'), [
        'title' => 'Falso', 'privacy' => 'public', 'categories' => categoryIds(), 'upload_token' => $start['token'],
    ])->assertJsonValidationErrors('audio');

    expect(Audio::count())->toBe(0);
});

test('el comando uploads:prune borra subidas abandonadas', function () {
    Storage::disk('local')->put('chunks/vieja.part', 'x');
    touch(Storage::disk('local')->path('chunks/vieja.part'), now()->subDays(2)->getTimestamp());
    Storage::disk('local')->put('chunks/reciente.part', 'x');

    $this->artisan('uploads:prune')->assertSuccessful();

    Storage::disk('local')->assertMissing('chunks/vieja.part');
    Storage::disk('local')->assertExists('chunks/reciente.part');
});
