<?php

return [

    // Disco donde se guardan los audios. Es privado: los archivos solo se sirven
    // a través de la ruta audios.stream. En producción puede ser 's3' (S3 o Cloudflare R2).
    'disk' => env('MEDIA_DISK', 'local'),

    // Disco de las portadas (público)
    'covers_disk' => env('COVERS_DISK', 'public'),

    // Tamaño máximo de un audio, en MB. La subida va por trozos, así que no depende
    // de upload_max_filesize de PHP.
    'max_upload_mb' => (int) env('MEDIA_MAX_UPLOAD_MB', 500),

    // Tamaño de cada trozo de la subida, en MB
    'chunk_mb' => (int) env('MEDIA_CHUNK_MB', 5),

    'allowed_extensions' => ['mp3', 'wav', 'm4a', 'ogg', 'oga', 'mp4', 'aac', 'flac'],

    // Procesar los audios con ffmpeg al subirlos (duración, MP3, volumen, forma de onda)
    'processing' => (bool) env('MEDIA_PROCESSING', true),

    // Binarios para procesar el audio. Si no existen, el audio se publica sin procesar.
    // En Laravel Cloud, deploy/ffmpeg.sh los descarga en bin/ffmpeg/ durante el build.
    'ffmpeg' => env('FFMPEG_BINARY', is_file(base_path('bin/ffmpeg/ffmpeg')) ? base_path('bin/ffmpeg/ffmpeg') : 'ffmpeg'),
    'ffprobe' => env('FFPROBE_BINARY', is_file(base_path('bin/ffmpeg/ffprobe')) ? base_path('bin/ffmpeg/ffprobe') : 'ffprobe'),

    // Tiempo máximo del procesado. Las colas Flex de Laravel Cloud cortan a los 90 s.
    'job_timeout' => (int) env('MEDIA_JOB_TIMEOUT', 3600),

    // URL pública del bucket de portadas en S3/R2 (en Laravel Cloud no se inyecta sola)
    'covers_url' => env('COVERS_PUBLIC_URL'),

    // Normalizar el volumen y convertir a MP3 al procesar
    'normalize' => (bool) env('MEDIA_NORMALIZE', true),
    'mp3_bitrate' => env('MEDIA_MP3_BITRATE', '128k'),

    // Número de barras de la forma de onda
    'waveform_points' => 160,

];
