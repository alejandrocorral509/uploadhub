<?php

namespace App\Support;

class YouTube
{
    private const HOSTS = ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com', 'youtu.be', 'www.youtu.be'];

    /**
     * Devuelve el ID del vídeo de cualquier enlace de YouTube, o null si no es válido.
     * Admite watch?v=, youtu.be/, /shorts/, /embed/, /live/ y /v/, con o sin www.
     */
    public static function id(?string $url): ?string
    {
        if (! $url) {
            return null;
        }

        $url = trim($url);
        if (! preg_match('#^https?://#i', $url)) {
            $url = 'https://'.$url;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $path = $parts['path'] ?? '';

        if (str_ends_with($host, 'youtu.be')) {
            $candidate = explode('/', trim($path, '/'))[0] ?? null;
        } elseif ($path === '/watch') {
            parse_str($parts['query'] ?? '', $query);
            $candidate = $query['v'] ?? null;
        } elseif (preg_match('#^/(shorts|embed|live|v)/([^/?]+)#', $path, $m)) {
            $candidate = $m[2];
        } else {
            $candidate = null;
        }

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }

    public static function embedUrl(string $id): string
    {
        return 'https://www.youtube-nocookie.com/embed/'.$id;
    }

    public static function thumbnailUrl(string $id): string
    {
        return 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg';
    }
}
