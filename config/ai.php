<?php

return [

    // Transcripción automática de los episodios: 'none' (desactivada) u 'openai' (Whisper)
    'transcription' => [
        'driver' => env('AI_TRANSCRIPTION_DRIVER', 'none'),
        'openai_key' => env('OPENAI_API_KEY'),
        'model' => env('AI_TRANSCRIPTION_MODEL', 'whisper-1'),
        'language' => env('AI_TRANSCRIPTION_LANGUAGE', 'es'),
    ],

    // Resumen y capítulos generados a partir de la transcripción: 'none' o 'anthropic' (Claude)
    'summary' => [
        'driver' => env('AI_SUMMARY_DRIVER', 'none'),
        'anthropic_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('AI_SUMMARY_MODEL', 'claude-opus-5-5'),
        'effort' => env('AI_SUMMARY_EFFORT', 'medium'),
    ],

];
