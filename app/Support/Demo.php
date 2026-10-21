<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class Demo
{
    public static function enabled(): bool
    {
        return (bool) config('demo.enabled');
    }

    // Cuentas de ejemplo creadas por DemoSeeder: no se pueden modificar en modo demo
    public static function isDemoAccount(?User $user): bool
    {
        return self::enabled() && $user && Str::endsWith($user->email, config('demo.accounts_domain'));
    }

    // ¿Ha llegado este usuario al límite diario? (null si no hay límite)
    public static function limitMessage(User $user, string $type): ?string
    {
        if (! self::enabled()) {
            return null;
        }

        [$query, $max, $label] = match ($type) {
            'episodes' => [$user->audios(), config('demo.max_episodes_per_day'), 'episodios'],
            'shows' => [$user->shows(), config('demo.max_shows_per_day'), 'programas'],
        };

        if ($query->where('created_at', '>=', now()->subDay())->count() >= $max) {
            return "En la demo cada cuenta puede crear {$max} {$label} al día. Los datos se reinician cada noche.";
        }

        return null;
    }
}
