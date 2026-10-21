<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class Media
{
    // URL pública de un archivo (portadas). En disco local se usa el host de la petición,
    // así funciona aunque APP_URL no lleve el puerto; en S3/R2, la URL del propio disco.
    public static function publicUrl(string $disk, string $path): string
    {
        if (config("filesystems.disks.{$disk}.driver") === 'local') {
            return asset('storage/'.ltrim($path, '/'));
        }

        if ($base = config('media.covers_url')) {
            return rtrim($base, '/').'/'.ltrim($path, '/');
        }

        return Storage::disk($disk)->url($path);
    }
}
