<?php

use App\Support\YouTube;

test('extrae el ID de todos los formatos de enlace', function (string $url) {
    expect(YouTube::id($url))->toBe('dQw4w9WgXcQ');
})->with([
    'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
    'http://youtube.com/watch?v=dQw4w9WgXcQ&list=PL123&t=42s',
    'https://m.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
    'https://youtu.be/dQw4w9WgXcQ?si=abc',
    'https://www.youtube.com/shorts/dQw4w9WgXcQ',
    'https://www.youtube.com/embed/dQw4w9WgXcQ',
    'https://www.youtube.com/live/dQw4w9WgXcQ?feature=share',
    'youtube.com/watch?v=dQw4w9WgXcQ',
]);

test('rechaza lo que no es un vídeo de YouTube', function (?string $url) {
    expect(YouTube::id($url))->toBeNull();
})->with([
    null,
    '',
    'https://vimeo.com/123456',
    'https://youtube.com.evil.com/watch?v=dQw4w9WgXcQ',
    'https://www.youtube.com/watch?v=corto',
    'https://www.youtube.com/channel/UC123',
    'javascript:alert(1)',
]);
