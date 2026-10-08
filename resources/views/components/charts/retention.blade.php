@props(['points', 'duration', 'height' => 240])

{{--
    Curva de retención: qué parte de las escuchas llega a cada punto del episodio.
    $points: lista de ['percent' => 0..100, 'share' => 0..1]. Línea de 2 px con un relleno suave debajo.
--}}
@php
    $width = 800;
    $padLeft = 44;
    $padBottom = 26;
    $padTop = 12;
    $plotW = $width - $padLeft;
    $plotH = $height - $padBottom - $padTop;
    $x = fn ($percent) => $padLeft + $percent / 100 * $plotW;
    $y = fn ($share) => $padTop + $plotH - $share * $plotH;
    $line = collect($points)->map(fn ($p) => round($x($p['percent']), 1).','.round($y($p['share']), 1))->implode(' L');
    $area = 'M'.$x(0).','.$y(0).' L'.$line.' L'.$x(100).','.$y(0).' Z';
    $time = fn ($percent) => gmdate($duration >= 3600 ? 'G:i:s' : 'i:s', (int) round($duration * $percent / 100));
    $tooltipPoints = collect($points)->map(fn ($p) => [
        'value' => round($p['share'] * 100).' %',
        'title' => 'llegan al minuto '.$time($p['percent']).' ('.$p['percent'].' % del episodio)',
        'x' => ($x($p['percent'])) / $width * 100,
        'y' => $y($p['share']) / $height * 100,
    ])->values();
@endphp

<div x-data="chart(@js($tooltipPoints))" class="relative" @mouseleave="active = null">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full overflow-visible" role="img"
         aria-label="Curva de retención: porcentaje de escuchas que llega a cada punto del episodio">
        @foreach ([0, 0.25, 0.5, 0.75, 1] as $tick)
            <line x1="{{ $padLeft }}" x2="{{ $width }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" stroke="#e5e7eb" stroke-width="1" />
            <text x="{{ $padLeft - 8 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" class="fill-gray-500 text-[11px] tabular-nums">{{ $tick * 100 }} %</text>
        @endforeach
        @foreach ([0, 25, 50, 75, 100] as $percent)
            <text x="{{ $x($percent) }}" y="{{ $height - 6 }}" text-anchor="{{ $percent === 0 ? 'start' : ($percent === 100 ? 'end' : 'middle') }}" class="fill-gray-500 text-[11px] tabular-nums">{{ $time($percent) }}</text>
        @endforeach

        <path d="{{ $area }}" fill="#4f46e5" fill-opacity="0.1" />
        <path d="M{{ $line }}" fill="none" stroke="#4f46e5" stroke-width="2" stroke-linejoin="round" stroke-linecap="round" />

        {{-- Cruz que sigue al puntero y punto en el valor más cercano --}}
        <g x-show="point" x-cloak>
            <line :x1="point && point.x * {{ $width }} / 100" :x2="point && point.x * {{ $width }} / 100" y1="{{ $padTop }}" y2="{{ $y(0) }}" stroke="#9ca3af" stroke-width="1" />
            <circle :cx="point && point.x * {{ $width }} / 100" :cy="point && point.y * {{ $height }} / 100" r="5" fill="#4f46e5" stroke="#fff" stroke-width="2" />
        </g>

        <rect x-ref="plot" x="{{ $padLeft }}" y="{{ $padTop }}" width="{{ $plotW }}" height="{{ $plotH }}" fill="transparent" @mousemove="track($event)" />
    </svg>

    <div x-show="point" x-cloak class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
         :style="point && `left: ${point.x}%`">
        <p class="text-sm font-bold text-gray-900" x-text="point?.value"></p>
        <p class="text-gray-500" x-text="point?.title"></p>
    </div>

    <details class="mt-2 text-xs text-gray-500">
        <summary class="cursor-pointer select-none">Ver los datos como tabla</summary>
        <table class="mt-2 w-full max-w-sm">
            <thead><tr><th class="py-1 text-left font-semibold">Momento</th><th class="py-1 text-right font-semibold">Escuchas que llegan</th></tr></thead>
            <tbody>
                @foreach ($points as $p)
                    <tr class="border-t border-gray-100"><td class="py-1 tabular-nums">{{ $time($p['percent']) }} ({{ $p['percent'] }} %)</td><td class="py-1 text-right tabular-nums">{{ round($p['share'] * 100) }} %</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</div>
