<?php

namespace App\Services\Ai;

use App\Contracts\Transcriber;
use App\Services\AudioProcessor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Transcripción con Whisper (API de OpenAI).
 *
 * La API admite archivos de hasta 25 MB, así que el audio se convierte antes a mono,
 * 16 kHz y 32 kbps (unos 14 MB por hora) y, si dura más de 50 minutos, se trocea.
 */
class WhisperTranscriber implements Transcriber
{
    private const SEGMENT_SECONDS = 3000;

    public function __construct(private AudioProcessor $processor) {}

    public function transcribe(string $path): array
    {
        $key = config('ai.transcription.openai_key');
        if (! $key) {
            throw new RuntimeException('Falta OPENAI_API_KEY para transcribir.');
        }

        if (! $this->processor->available()) {
            // Sin ffmpeg no se puede trocear: se envía tal cual (solo funciona si pesa menos de 25 MB)
            return $this->request($path, 0);
        }

        $duration = $this->processor->duration($path);
        $text = [];
        $segments = [];

        for ($offset = 0; $offset < max(1, $duration); $offset += self::SEGMENT_SECONDS) {
            $part = sys_get_temp_dir().'/'.Str::uuid().'.mp3';

            try {
                $result = Process::timeout(900)->run([
                    config('media.ffmpeg'), '-y', '-v', 'error',
                    '-ss', (string) $offset, '-t', (string) self::SEGMENT_SECONDS,
                    '-i', $path,
                    '-vn', '-ac', '1', '-ar', '16000', '-b:a', '32k',
                    $part,
                ]);

                if (! $result->successful()) {
                    throw new RuntimeException('No se pudo preparar el audio para transcribir.');
                }

                $chunk = $this->request($part, $offset);
                $text[] = $chunk['text'];
                array_push($segments, ...$chunk['segments']);
            } finally {
                @unlink($part);
            }
        }

        return ['text' => trim(implode(' ', $text)), 'segments' => $segments];
    }

    private function request(string $path, int $offset): array
    {
        $response = Http::withToken(config('ai.transcription.openai_key'))
            ->timeout(600)
            ->retry(2, 2000, throw: false)
            ->attach('file', fopen($path, 'rb'), basename($path))
            ->post('https://api.openai.com/v1/audio/transcriptions', [
                'model' => config('ai.transcription.model'),
                'language' => config('ai.transcription.language'),
                'response_format' => 'verbose_json',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Error de la API de transcripción: '.$response->status());
        }

        $segments = collect($response->json('segments', []))
            ->map(fn ($s) => ['start' => (float) $s['start'] + $offset, 'text' => trim($s['text'])])
            ->all();

        return ['text' => trim((string) $response->json('text')), 'segments' => $segments];
    }
}
