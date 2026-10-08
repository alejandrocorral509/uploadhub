<?php

use App\Models\Audio;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
    $this->seed(CategoriesSeeder::class);
});

function audioOf(User $owner, string $privacy): Audio
{
    Storage::disk('local')->put('audios/prueba.mp3', 'contenido');

    return Audio::create([
        'user_id' => $owner->id,
        'title' => 'Episodio',
        'filepath' => 'audios/prueba.mp3',
        'filetype' => 'audio/mpeg',
        'privacy' => $privacy,
    ]);
}

test('the seeder creates the categories needed to upload', function () {
    expect(Category::count())->toBeGreaterThan(0);
});

test('uploaded audio files are stored on the private disk', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/audios', [
        'title' => 'Mi episodio',
        'audio' => UploadedFile::fake()->create('episodio.mp3', 100, 'audio/mpeg'),
        'privacy' => 'public',
        'categories' => [Category::first()->id],
        'tags' => 'uno, dos',
    ])->assertRedirect(route('dashboard'));

    $audio = Audio::first();
    Storage::disk('local')->assertExists($audio->filepath);
    Storage::disk('public')->assertMissing($audio->filepath);
    expect($audio->tags)->toHaveCount(2);
});

test('public audio can be streamed by guests', function () {
    $audio = audioOf(User::factory()->create(), 'public');

    $this->get(route('audios.stream', $audio))->assertOk();
});

test('private audio can only be streamed by its owner', function () {
    $owner = User::factory()->create();
    $audio = audioOf($owner, 'private');

    $this->get(route('audios.stream', $audio))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('audios.stream', $audio))->assertNotFound();
    $this->actingAs($owner)->get(route('audios.stream', $audio))->assertOk();
});

test('private audio cannot be downloaded by other users', function () {
    $owner = User::factory()->create();
    $audio = audioOf($owner, 'private');

    $this->actingAs(User::factory()->create())->get(route('audios.download', $audio))->assertNotFound();
    $this->actingAs($owner)->get(route('audios.download', $audio))->assertOk();
});

test('private audio cannot be liked or commented by other users', function () {
    $audio = audioOf(User::factory()->create(), 'private');
    $other = User::factory()->create();

    $this->actingAs($other)->post(route('audios.toggle-like', $audio))->assertNotFound();
    $this->actingAs($other)->post(route('comments.store', $audio), ['content' => 'Hola'])->assertNotFound();

    expect($audio->likes()->count())->toBe(0);
    expect($audio->comments()->count())->toBe(0);
});

test('users cannot delete audio from other users', function () {
    $audio = audioOf(User::factory()->create(), 'public');

    $this->actingAs(User::factory()->create())->delete(route('audios.destroy', $audio))->assertForbidden();

    expect(Audio::count())->toBe(1);
});

test('deleting an audio removes its file', function () {
    $owner = User::factory()->create();
    $audio = audioOf($owner, 'public');

    $this->actingAs($owner)->delete(route('audios.destroy', $audio));

    Storage::disk('local')->assertMissing('audios/prueba.mp3');
    expect(Audio::count())->toBe(0);
});

test('guests can browse public audio but not private audio', function () {
    $owner = User::factory()->create();
    $public = audioOf($owner, 'public');
    $private = audioOf($owner, 'private');
    $private->update(['title' => 'Borrador secreto']);

    $this->get(route('audios.public'))
        ->assertOk()
        ->assertSee($public->title)
        ->assertDontSee('Borrador secreto')
        ->assertSee(route('login'));
});

test('the stream never trusts the stored file type', function () {
    $audio = audioOf(User::factory()->create(), 'public');
    $audio->update(['filetype' => 'text/html']);

    $response = $this->get(route('audios.stream', $audio));

    $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    expect($response->headers->get('Content-Type'))->not->toContain('html');
});
