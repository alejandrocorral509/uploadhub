<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Subida de archivos grandes por trozos.
 *
 * El navegador divide el archivo en trozos de unos pocos MB y los envía en orden.
 * Así la subida no depende de upload_max_filesize de PHP y se puede mostrar el progreso.
 * Los trozos se van añadiendo a un archivo temporal en el disco local; al publicar
 * el episodio, el archivo se comprueba y se mueve al disco de medios.
 */
class ChunkedUpload
{
    private const TTL_HOURS = 24;

    public function start(User $user, string $filename, int $size): array
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $maxBytes = config('media.max_upload_mb') * 1024 * 1024;

        if (! in_array($extension, config('media.allowed_extensions'), true)) {
            throw ValidationException::withMessages([
                'file' => 'Formato no admitido. Usa '.strtoupper(implode(', ', config('media.allowed_extensions'))).'.',
            ]);
        }

        if ($size <= 0 || $size > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no puede superar '.config('media.max_upload_mb').' MB.',
            ]);
        }

        $token = (string) Str::uuid();
        Storage::disk('local')->put($this->partPath($token), '');

        Cache::put($this->key($token), [
            'user_id' => $user->id,
            'extension' => $extension,
            'size' => $size,
            'received' => 0,
        ], now()->addHours(self::TTL_HOURS));

        return [
            'token' => $token,
            'chunk_size' => config('media.chunk_mb') * 1024 * 1024,
        ];
    }

    public function append(string $token, User $user, int $offset, UploadedFile $chunk): array
    {
        $meta = $this->meta($token, $user);

        // Los trozos deben llegar en orden; si se repite uno (reintento), se responde con lo recibido
        if ($offset !== $meta['received']) {
            return ['received' => $meta['received'], 'complete' => $meta['received'] === $meta['size']];
        }

        if ($meta['size'] < $meta['received'] + $chunk->getSize()) {
            throw ValidationException::withMessages(['chunk' => 'El trozo supera el tamaño declarado.']);
        }

        $target = fopen(Storage::disk('local')->path($this->partPath($token)), 'ab');
        $source = fopen($chunk->getRealPath(), 'rb');
        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        $meta['received'] += $chunk->getSize();
        Cache::put($this->key($token), $meta, now()->addHours(self::TTL_HOURS));

        return ['received' => $meta['received'], 'complete' => $meta['received'] === $meta['size']];
    }

    public function isComplete(?string $token, User $user): bool
    {
        $meta = $token ? Cache::get($this->key($token)) : null;

        return $meta && $meta['user_id'] === $user->id && $meta['received'] === $meta['size'];
    }

    /**
     * Comprueba el contenido real del archivo y lo mueve al disco de medios.
     *
     * @return array{path: string, mime: string}
     */
    public function claim(string $token, User $user): array
    {
        $meta = $this->meta($token, $user);
        $localPath = Storage::disk('local')->path($this->partPath($token));
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($localPath) ?: 'application/octet-stream';

        if (! self::isAllowedMime($mime)) {
            $this->discard($token);
            throw ValidationException::withMessages(['audio' => 'El archivo no es un audio válido.']);
        }

        $path = 'audios/'.Str::uuid().'.'.$meta['extension'];
        $stream = fopen($localPath, 'rb');
        Storage::disk(config('media.disk'))->writeStream($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        $this->discard($token);

        return ['path' => $path, 'mime' => $mime];
    }

    // Cancelar una subida: solo la persona que la empezó
    public function cancel(string $token, User $user): void
    {
        $this->meta($token, $user);
        $this->discard($token);
    }

    public function discard(string $token): void
    {
        Storage::disk('local')->delete($this->partPath($token));
        Cache::forget($this->key($token));
    }

    // Borra las subidas a medias que llevan más de un día abandonadas
    public function prune(): int
    {
        $deleted = 0;
        foreach (Storage::disk('local')->files('chunks') as $file) {
            if (Storage::disk('local')->lastModified($file) < now()->subHours(self::TTL_HOURS)->getTimestamp()) {
                Storage::disk('local')->delete($file);
                $deleted++;
            }
        }

        return $deleted;
    }

    public static function isAllowedMime(string $mime): bool
    {
        return str_starts_with($mime, 'audio/') || in_array($mime, ['video/mp4', 'application/ogg'], true);
    }

    private function meta(string $token, User $user): array
    {
        $meta = Cache::get($this->key($token));

        if (! $meta || $meta['user_id'] !== $user->id) {
            abort(404, 'Subida no encontrada o caducada.');
        }

        return $meta;
    }

    private function key(string $token): string
    {
        return 'chunked-upload:'.$token;
    }

    private function partPath(string $token): string
    {
        return 'chunks/'.$token.'.part';
    }
}
