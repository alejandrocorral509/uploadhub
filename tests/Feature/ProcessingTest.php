<?php

use App\Jobs\ProcessAudio;
use App\Jobs\TranscribeAudio;
use App\Models\Audio;
use App\Services\AudioProcessor;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

test('sin ffmpeg el episodio se publica igualmente', function () {
    config(['media.processing' => true, 'media.ffmpeg' => 'no-existe-ffmpeg', 'media.ffprobe' => 'no-existe-ffprobe']);
    $audio = Audio::factory()->withFile()->create(['processing_status' => Audio::STATUS_PENDING]);

    ProcessAudio::dispatchSync($audio);

    expect($audio->fresh()->processing_status)->toBe(Audio::STATUS_READY);
});

test('con ffmpeg: lee la duración, convierte a MP3 y genera la forma de onda', function () {
    config(['media.processing' => true]);
    $processor = new AudioProcessor;
    if (! $processor->available()) {
        $this->markTestSkipped('ffmpeg no está instalado');
    }

    // Un WAV de 3 segundos generado con ffmpeg
    $wav = sys_get_temp_dir().'/uploadhub-test.wav';
    Process::run([config('media.ffmpeg'), '-y', '-v', 'error', '-f', 'lavfi', '-i', 'sine=frequency=440:duration=3', $wav])->throw();
    Storage::disk('local')->put('audios/original.wav', file_get_contents($wav));
    $audio = Audio::factory()->create(['filepath' => 'audios/original.wav', 'filetype' => 'audio/x-wav', 'duration' => null]);

    ProcessAudio::dispatchSync($audio);

    $audio->refresh();
    expect($audio->processing_status)->toBe(Audio::STATUS_READY)
        ->and($audio->duration)->toBe(3)
        ->and($audio->filetype)->toBe('audio/mpeg')
        ->and($audio->filepath)->toEndWith('.mp3')
        ->and($audio->waveform)->toHaveCount(config('media.waveform_points'))
        ->and(max($audio->waveform))->toBe(100);
    Storage::disk('local')->assertMissing('audios/original.wav');
    Storage::disk('local')->assertExists($audio->filepath);
});

test('si la transcripción está activada se encola al terminar el procesado', function () {
    Bus::fake([TranscribeAudio::class]);
    config(['media.processing' => false, 'ai.transcription.driver' => 'openai']);
    $audio = Audio::factory()->withFile()->create();

    (new ProcessAudio($audio))->handle(app(AudioProcessor::class));

    Bus::assertDispatched(TranscribeAudio::class);
    expect($audio->fresh()->transcript_status)->toBe('pending');
});
