<?php

namespace App\Jobs;

use App\Contracts\Summarizer;
use App\Contracts\Transcriber;
use App\Models\Audio;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

// Transcribe el episodio y, si está activado, genera el resumen y los capítulos
class TranscribeAudio implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 2;

    public function __construct(public Audio $audio) {}

    public function handle(Transcriber $transcriber, Summarizer $summarizer): void
    {
        $audio = $this->audio->fresh();
        if (! $audio || ! $audio->filepath || ! $audio->fileDisk()) {
            return;
        }

        $audio->update(['transcript_status' => 'processing']);

        $local = storage_path('app/processing/'.Str::uuid().'.'.pathinfo($audio->filepath, PATHINFO_EXTENSION));
        @mkdir(dirname($local), 0775, true);

        try {
            $in = Storage::disk($audio->fileDisk())->readStream($audio->filepath);
            $out = fopen($local, 'wb');
            stream_copy_to_stream($in, $out);
            fclose($out);
            if (is_resource($in)) {
                fclose($in);
            }

            $result = $transcriber->transcribe($local);
            $audio->update(['transcript' => $result['text']]);

            if (config('ai.summary.driver') !== 'none' && $result['segments'] !== []) {
                $summary = $summarizer->summarize($audio->title, $audio->description, $result['segments']);
                $audio->update(['summary' => $summary['summary'], 'chapters' => $summary['chapters']]);
            }

            $audio->update(['transcript_status' => 'ready']);
        } finally {
            @unlink($local);
        }
    }

    public function failed(?Throwable $e): void
    {
        Log::warning('Error al transcribir el audio '.$this->audio->id.': '.$e?->getMessage());
        $this->audio->fresh()?->update(['transcript_status' => 'failed']);
    }
}
