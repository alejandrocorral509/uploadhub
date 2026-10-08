<?php

namespace App\Jobs;

use App\Models\Audio;
use App\Services\AudioProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Procesa un episodio recién subido:
 * 1. Lee la duración.
 * 2. Si es audio, lo convierte a MP3 con el volumen normalizado (así siempre se puede descargar en MP3).
 * 3. Genera la forma de onda.
 * 4. Lanza la transcripción, si está activada.
 */
class ProcessAudio implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 2;

    public function __construct(public Audio $audio) {}

    public function handle(AudioProcessor $processor): void
    {
        $audio = $this->audio->fresh();
        if (! $audio || ! $audio->filepath) {
            return;
        }

        if (! config('media.processing') || ! $processor->available()) {
            $audio->update(['processing_status' => Audio::STATUS_READY]);
            $this->queueTranscription($audio);

            return;
        }

        $audio->update(['processing_status' => Audio::STATUS_PROCESSING]);

        $disk = Storage::disk($audio->fileDisk() ?? config('media.disk'));
        $workDir = storage_path('app/processing/'.Str::uuid());
        @mkdir($workDir, 0775, true);

        try {
            $input = $workDir.'/input.'.pathinfo($audio->filepath, PATHINFO_EXTENSION);
            $this->download($disk, $audio->filepath, $input);

            $source = $input;
            $updates = ['duration' => $processor->duration($input)];

            if (! $audio->isVideo()) {
                $output = $workDir.'/output.mp3';
                $processor->toNormalizedMp3($input, $output);

                $newPath = 'audios/'.Str::uuid().'.mp3';
                $stream = fopen($output, 'rb');
                Storage::disk(config('media.disk'))->writeStream($newPath, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                $disk->delete($audio->filepath);
                $updates['filepath'] = $newPath;
                $updates['filetype'] = 'audio/mpeg';
                $source = $output;
            }

            $updates['waveform'] = $processor->waveform($source, config('media.waveform_points'));
            $updates['processing_status'] = Audio::STATUS_READY;

            $audio->update($updates);
            $this->queueTranscription($audio);
        } finally {
            $this->cleanUp($workDir);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Error al procesar el audio '.$this->audio->id.': '.$e?->getMessage());

        // El archivo original sigue ahí: si se puede reproducir, mejor publicarlo sin procesar
        $this->audio->fresh()?->update(['processing_status' => Audio::STATUS_FAILED]);
    }

    private function queueTranscription(Audio $audio): void
    {
        if (config('ai.transcription.driver') !== 'none' && ! $audio->isVideo()) {
            $audio->update(['transcript_status' => 'pending']);
            TranscribeAudio::dispatch($audio);
        }
    }

    private function download($disk, string $path, string $target): void
    {
        $in = $disk->readStream($path);
        $out = fopen($target, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($out);
        if (is_resource($in)) {
            fclose($in);
        }
    }

    private function cleanUp(string $dir): void
    {
        foreach (glob($dir.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
}
