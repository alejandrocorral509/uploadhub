<?php

use App\Contracts\Summarizer;
use App\Contracts\Transcriber;
use App\Jobs\TranscribeAudio;
use App\Models\Audio;
use App\Services\Ai\WhisperTranscriber;
use App\Services\AudioProcessor;
use Illuminate\Support\Facades\Http;

test('la transcripción guarda el texto, el resumen y los capítulos', function () {
    config(['ai.summary.driver' => 'anthropic']);

    app()->instance(Transcriber::class, new class implements Transcriber
    {
        public function transcribe(string $path): array
        {
            return ['text' => 'Hola, hoy hablamos de colas.', 'segments' => [['start' => 0.0, 'text' => 'Hola,'], ['start' => 2.5, 'text' => 'hoy hablamos de colas.']]];
        }
    });

    app()->instance(Summarizer::class, new class implements Summarizer
    {
        public function summarize(string $title, ?string $description, array $segments): array
        {
            return ['summary' => "Resumen de «{$title}» con ".count($segments).' fragmentos.', 'chapters' => [['start' => 0, 'title' => 'Introducción']]];
        }
    });

    $audio = Audio::factory()->withFile()->create(['title' => 'Colas']);

    TranscribeAudio::dispatchSync($audio);

    $audio->refresh();
    expect($audio->transcript_status)->toBe('ready')
        ->and($audio->transcript)->toBe('Hola, hoy hablamos de colas.')
        ->and($audio->summary)->toBe('Resumen de «Colas» con 2 fragmentos.')
        ->and($audio->chapters)->toBe([['start' => 0, 'title' => 'Introducción']]);

    // Y se muestra en la página del episodio, que además es buscable por lo que se dice
    $this->get($audio->url())->assertSee('Introducción')->assertSee('Transcripción');
    $this->get(route('explore', ['q' => 'hablamos de colas']))->assertSee('Colas');
});

test('si la transcripción falla, el episodio queda marcado y sigue publicado', function () {
    app()->instance(Transcriber::class, new class implements Transcriber
    {
        public function transcribe(string $path): array
        {
            throw new RuntimeException('API caída');
        }
    });

    $audio = Audio::factory()->withFile()->create();

    try {
        TranscribeAudio::dispatchSync($audio);
    } catch (RuntimeException) {
        // dispatchSync relanza la excepción; en una cola real se llamaría a failed()
        (new TranscribeAudio($audio))->failed(new RuntimeException('API caída'));
    }

    expect($audio->fresh()->transcript_status)->toBe('failed');
    $this->get($audio->url())->assertOk();
});

test('Whisper recibe el audio y las marcas de tiempo se conservan', function () {
    config(['ai.transcription.openai_key' => 'sk-test', 'ai.transcription.model' => 'whisper-1']);
    Http::fake([
        'api.openai.com/*' => Http::response([
            'text' => 'Hola mundo',
            'segments' => [['start' => 0.0, 'text' => ' Hola'], ['start' => 1.2, 'text' => ' mundo']],
        ]),
    ]);

    // Sin ffmpeg se envía el archivo tal cual
    $processor = Mockery::mock(AudioProcessor::class);
    $processor->shouldReceive('available')->andReturnFalse();
    $path = tempnam(sys_get_temp_dir(), 'aud');
    file_put_contents($path, 'audio');

    $result = (new WhisperTranscriber($processor))->transcribe($path);

    expect($result['text'])->toBe('Hola mundo')
        ->and($result['segments'][1])->toBe(['start' => 1.2, 'text' => 'mundo']);
    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk-test')
        && str_contains($request->url(), '/v1/audio/transcriptions'));
});

test('sin clave de API la transcripción avisa del problema', function () {
    config(['ai.transcription.openai_key' => null]);

    expect(fn () => app(WhisperTranscriber::class)->transcribe('x'))->toThrow(RuntimeException::class, 'OPENAI_API_KEY');
});
