@props(['points', 'label' => 'Escuchas', 'height' => 220])

{{--
    Gráfica de columnas en SVG generado en el servidor.
    $points: lista de ['label' => texto corto del eje, 'title' => texto del tooltip, 'value' => número]
    Columnas de 24 px como máximo con el extremo redondeado (4 px) y la base recta, separadas por 2 px.
--}}
@php
    $width = 800;
    $padLeft = 44;
    $padBottom = 26;
    $padTop = 10;
    $plotW = $width - $padLeft;
    $plotH = $height - $padBottom - $padTop;
    $count = max(1, count($points));
    $scale = \App\Support\ChartScale::nice(collect($points)->max('value') ?? 0);
    $band = $plotW / $count;
    $barW = min(24, max(1, $band - 2));
    $radius = min(4, $barW / 2);
    $labelEvery = (int) ceil($count / 8);
    $y = fn ($v) => $padTop + $plotH - ($v / $scale['max']) * $plotH;
    $tooltipPoints = collect($points)->map(fn ($p) => ['title' => $p['title'], 'value' => number_format($p['value'], 0, ',', '.')])->values();
@endphp

<div x-data="chart(@js($tooltipPoints))" class="relative" @mouseleave="active = null">
    <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-auto w-full overflow-visible" role="img" aria-label="{{ $label }} por día">
        {{-- Rejilla y eje Y --}}
        @foreach ($scale['ticks'] as $tick)
            <line x1="{{ $padLeft }}" x2="{{ $width }}" y1="{{ $y($tick) }}" y2="{{ $y($tick) }}" stroke="#e5e7eb" stroke-width="1" />
            <text x="{{ $padLeft - 8 }}" y="{{ $y($tick) + 4 }}" text-anchor="end" class="fill-gray-500 text-[11px] tabular-nums">{{ number_format($tick, 0, ',', '.') }}</text>
        @endforeach

        @foreach ($points as $i => $point)
            @php
                $x = $padLeft + $i * $band + ($band - $barW) / 2;
                $top = $y($point['value']);
                $base = $y(0);
                $h = $base - $top;
            @endphp
            @if ($point['value'] > 0)
                @php($r = min($radius, $h))
                <path d="M{{ $x }},{{ $base }} V{{ $top + $r }} Q{{ $x }},{{ $top }} {{ $x + $r }},{{ $top }} H{{ $x + $barW - $r }} Q{{ $x + $barW }},{{ $top }} {{ $x + $barW }},{{ $top + $r }} V{{ $base }} Z"
                      class="transition-opacity" fill="#4f46e5" :opacity="active === null || active === {{ $i }} ? 1 : 0.45" />
            @endif
            {{-- Zona sensible: toda la columna, no solo la barra --}}
            <rect x="{{ $padLeft + $i * $band }}" y="{{ $padTop }}" width="{{ $band }}" height="{{ $plotH }}" fill="transparent" @mouseenter="active = {{ $i }}" />
            @if ($i % $labelEvery === 0)
                <text x="{{ $padLeft + $i * $band + $band / 2 }}" y="{{ $height - 6 }}" text-anchor="middle" class="fill-gray-500 text-[11px]">{{ $point['label'] }}</text>
            @endif
        @endforeach
    </svg>

    <div x-show="point" x-cloak class="pointer-events-none absolute top-0 z-10 -translate-x-1/2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs shadow-lg"
         :style="active !== null && `left: calc({{ $padLeft / $width * 100 }}% + {{ ($plotW / $width) * 100 }}% * ${(active + 0.5) / {{ $count }}})`">
        <p class="text-sm font-bold text-gray-900" x-text="point?.value"></p>
        <p class="text-gray-500" x-text="point?.title"></p>
    </div>

    <details class="mt-2 text-xs text-gray-500">
        <summary class="cursor-pointer select-none">Ver los datos como tabla</summary>
        <table class="mt-2 w-full max-w-sm">
            <thead><tr><th class="py-1 text-left font-semibold">Día</th><th class="py-1 text-right font-semibold">{{ $label }}</th></tr></thead>
            <tbody>
                @foreach ($points as $point)
                    <tr class="border-t border-gray-100"><td class="py-1">{{ $point['title'] }}</td><td class="py-1 text-right tabular-nums">{{ number_format($point['value'], 0, ',', '.') }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </details>
</div>
