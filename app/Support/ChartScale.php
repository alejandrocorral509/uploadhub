<?php

namespace App\Support;

// Escala "redonda" para el eje Y de las gráficas: máximos y marcas en 1, 2, 5 × 10^n
class ChartScale
{
    /** @return array{max: float, ticks: list<float>} */
    public static function nice(float $maxValue, int $tickCount = 4): array
    {
        if ($maxValue <= 0) {
            return ['max' => $tickCount, 'ticks' => range(0, $tickCount)];
        }

        $rough = $maxValue / $tickCount;
        $magnitude = 10 ** floor(log10($rough));
        // 2,5 solo cuando el paso es de decenas o más (25, 250…): por debajo daría marcas con decimales
        $multipliers = $magnitude >= 10 ? [1, 2, 2.5, 5, 10] : [1, 2, 5, 10];
        $step = collect($multipliers)
            ->map(fn ($m) => $m * $magnitude)
            ->first(fn ($s) => $s >= $rough);

        // Recuentos: nunca marcas con decimales
        $step = max(1, $step);
        $max = ceil($maxValue / $step) * $step;

        return ['max' => $max, 'ticks' => range(0, $max, $step)];
    }
}
