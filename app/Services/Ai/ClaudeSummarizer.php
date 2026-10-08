<?php

namespace App\Services\Ai;

use Anthropic\Client;
use App\Contracts\Summarizer;
use RuntimeException;

/**
 * Resumen y capítulos de un episodio con Claude.
 *
 * Usa salida estructurada (esquema JSON) para que la respuesta siempre se pueda leer,
 * y el respaldo del servidor ('fallbacks' => 'default'): si el modelo rechaza la petición
 * por sus políticas, la API la repite con otro modelo dentro de la misma llamada.
 */
class ClaudeSummarizer implements Summarizer
{
    private const SYSTEM = <<<'TXT'
Eres el editor de una plataforma de podcasts en español. Recibes la transcripción de un episodio
con marcas de tiempo en segundos y devuelves:
- summary: un resumen de 3 a 5 frases, en español, en tercera persona, sin inventar nada que no se diga.
- chapters: entre 3 y 12 capítulos en orden. Cada uno empieza en un segundo que aparezca en la
  transcripción (el primero en 0) y tiene un título corto y descriptivo, de 2 a 8 palabras.
  Si el episodio es muy corto o trata un único tema, puede tener menos capítulos.
TXT;

    public function summarize(string $title, ?string $description, array $segments): array
    {
        $key = config('ai.summary.anthropic_key');
        if (! $key) {
            throw new RuntimeException('Falta ANTHROPIC_API_KEY para generar el resumen.');
        }

        $transcript = collect($segments)
            ->map(fn ($s) => '['.(int) $s['start'].'] '.$s['text'])
            ->implode("\n");

        $client = new Client(apiKey: $key);

        $message = $client->beta->messages->create(
            model: config('ai.summary.model'),
            maxTokens: 16000,
            betas: ['server-side-fallback-2026-07-01'],
            fallbacks: 'default',
            system: self::SYSTEM,
            outputConfig: [
                'effort' => config('ai.summary.effort'),
                'format' => [
                    'type' => 'json_schema',
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'summary' => ['type' => 'string'],
                            'chapters' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'start' => ['type' => 'integer'],
                                        'title' => ['type' => 'string'],
                                    ],
                                    'required' => ['start', 'title'],
                                    'additionalProperties' => false,
                                ],
                            ],
                        ],
                        'required' => ['summary', 'chapters'],
                        'additionalProperties' => false,
                    ],
                ],
            ],
            messages: [[
                'role' => 'user',
                'content' => "Título: {$title}\nDescripción: ".($description ?: '(sin descripción)')."\n\nTranscripción:\n{$transcript}",
            ]],
        );

        if ($message->stopReason === 'refusal') {
            throw new RuntimeException('El modelo no ha podido resumir este episodio.');
        }

        $json = null;
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json = json_decode($block->text, true);
                break;
            }
        }

        if (! is_array($json) || ! isset($json['summary'], $json['chapters'])) {
            throw new RuntimeException('Respuesta inesperada al generar el resumen.');
        }

        $chapters = collect($json['chapters'])
            ->filter(fn ($c) => isset($c['start'], $c['title']))
            ->map(fn ($c) => ['start' => max(0, (int) $c['start']), 'title' => trim($c['title'])])
            ->sortBy('start')
            ->values()
            ->all();

        return ['summary' => trim($json['summary']), 'chapters' => $chapters];
    }
}
