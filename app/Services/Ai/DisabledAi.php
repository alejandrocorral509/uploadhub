<?php

namespace App\Services\Ai;

use App\Contracts\Summarizer;
use App\Contracts\Transcriber;
use RuntimeException;

// Implementación vacía cuando la IA está desactivada (AI_*_DRIVER=none)
class DisabledAi implements Summarizer, Transcriber
{
    public function transcribe(string $path): array
    {
        throw new RuntimeException('La transcripción está desactivada.');
    }

    public function summarize(string $title, ?string $description, array $segments): array
    {
        throw new RuntimeException('El resumen está desactivado.');
    }
}
