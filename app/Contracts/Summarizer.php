<?php

namespace App\Contracts;

interface Summarizer
{
    /**
     * Genera un resumen y capítulos a partir de una transcripción con marcas de tiempo.
     *
     * @param  list<array{start: float, text: string}>  $segments
     * @return array{summary: string, chapters: list<array{start: int, title: string}>}
     */
    public function summarize(string $title, ?string $description, array $segments): array;
}
