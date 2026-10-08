<?php

namespace App\Contracts;

interface Transcriber
{
    /**
     * Transcribe un archivo de audio local.
     *
     * @return array{text: string, segments: list<array{start: float, text: string}>}
     */
    public function transcribe(string $path): array;
}
