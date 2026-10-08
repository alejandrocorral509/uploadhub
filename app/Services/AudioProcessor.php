<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

// Envoltorio de ffmpeg/ffprobe para analizar y convertir audios
class AudioProcessor
{
    private ?bool $available = null;

    public function available(): bool
    {
        if ($this->available !== null) {
            return $this->available;
        }

        try {
            $this->available = Process::timeout(10)->run([config('media.ffprobe'), '-version'])->successful()
                && Process::timeout(10)->run([config('media.ffmpeg'), '-version'])->successful();
        } catch (\Throwable) {
            $this->available = false;
        }

        return $this->available;
    }

    // Duración en segundos
    public function duration(string $path): ?int
    {
        $result = Process::timeout(60)->run([
            config('media.ffprobe'), '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $path,
        ]);

        if (! $result->successful() || ! is_numeric(trim($result->output()))) {
            throw new RuntimeException('No se pudo leer el audio: '.trim($result->errorOutput()));
        }

        return (int) round((float) trim($result->output()));
    }

    // Convierte a MP3 normalizando el volumen (estándar de podcasts: -16 LUFS)
    public function toNormalizedMp3(string $input, string $output): void
    {
        $filters = config('media.normalize') ? ['-af', 'loudnorm=I=-16:TP=-1.5:LRA=11'] : [];

        $result = Process::timeout(1800)->run([
            config('media.ffmpeg'), '-y', '-v', 'error',
            '-i', $input,
            '-vn',
            ...$filters,
            '-ar', '44100',
            '-codec:a', 'libmp3lame',
            '-b:a', config('media.mp3_bitrate'),
            $output,
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('No se pudo convertir el audio: '.trim($result->errorOutput()));
        }
    }

    /**
     * Forma de onda: el pico de volumen (0-100) de cada uno de los $points tramos del audio.
     *
     * @return array<int, int>
     */
    public function waveform(string $path, int $points): array
    {
        // Mono a 1000 Hz en PCM de 16 bits: unos 7 MB por hora de audio
        $result = Process::timeout(600)->run([
            config('media.ffmpeg'), '-v', 'error',
            '-i', $path,
            '-ac', '1', '-ar', '1000',
            '-f', 's16le', '-acodec', 'pcm_s16le',
            '-',
        ]);

        if (! $result->successful()) {
            throw new RuntimeException('No se pudo generar la forma de onda.');
        }

        $samples = unpack('s*', $result->output()) ?: [];
        $total = count($samples);
        if ($total === 0) {
            return array_fill(0, $points, 0);
        }

        $samples = array_values($samples);
        $bucket = max(1, intdiv($total, $points));
        $peaks = [];

        for ($i = 0; $i < $points; $i++) {
            $max = 0;
            $end = min($total, ($i + 1) * $bucket);
            for ($j = $i * $bucket; $j < $end; $j++) {
                $v = abs($samples[$j]);
                if ($v > $max) {
                    $max = $v;
                }
            }
            $peaks[] = $max;
        }

        $highest = max($peaks) ?: 1;

        return array_map(fn ($p) => (int) round($p / $highest * 100), $peaks);
    }
}
