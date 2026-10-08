@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $points = collect($daily)->map(fn ($d) => [
        'label' => $d['date']->translatedFormat($days > 90 ? 'M' : 'j M'),
        'title' => $d['date']->translatedFormat('l j \d\e F'),
        'value' => $d['plays'],
    ])->all();
@endphp

<x-app-layout title="Estadísticas">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Estadísticas</h1>
                <p class="mt-1 text-sm text-gray-500">Cómo se escuchan tus episodios.</p>
            </div>
            {{-- Rango de fechas: un único filtro arriba que afecta a todo --}}
            <nav class="inline-flex rounded-lg bg-gray-100 p-1 text-sm font-medium" aria-label="Periodo">
                @foreach ($periods as $value => $label)
                    <a href="{{ route('stats.index', ['dias' => $value]) }}" @class([
                        'rounded-md px-3 py-1.5',
                        'bg-white text-gray-900 shadow-sm' => $days === $value,
                        'text-gray-500 hover:text-gray-800' => $days !== $value,
                    ]) @if ($days === $value) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-3">
            <x-stat-tile label="Escuchas" :value="$fmt($totals['plays'])" hint="Cada vez que alguien le da al play (no se repite si vuelve en menos de 30 min)" />
            <x-stat-tile label="Oyentes únicos" :value="$fmt($totals['listeners'])" />
            <x-stat-tile label="Escuchado de media" :value="$totals['completion'] !== null ? round($totals['completion'] * 100).' %' : '—'" hint="Qué parte de cada episodio se escucha" />
            <x-stat-tile label="Me gusta nuevos" :value="$fmt($totals['likes'])" />
            <x-stat-tile label="Suscriptores nuevos" :value="$fmt($totals['subscribers'])" />
            <x-stat-tile label="Comentarios" :value="$fmt($totals['comments'])" />
        </div>

        <section class="card mt-6 p-5">
            <h2 class="font-bold">Escuchas por día</h2>
            <p class="text-sm text-gray-500">Últimos {{ $periods[$days] }}</p>
            <div class="mt-4">
                @if ($totals['plays'] === 0)
                    <x-empty-state icon="fa-chart-column" title="Aún no hay escuchas en este periodo" text="Comparte tus episodios o el feed RSS de tus programas para empezar a ver datos." />
                @else
                    <x-charts.columns :points="$points" />
                @endif
            </div>
        </section>

        <section class="card mt-6 overflow-hidden">
            <h2 class="px-5 pt-5 font-bold">Episodios más escuchados</h2>
            @if ($episodes->isEmpty())
                <p class="p-5 text-sm text-gray-500">Sin datos en este periodo.</p>
            @else
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-y border-gray-200 bg-gray-50 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                            <tr>
                                <th scope="col" class="px-5 py-3">Episodio</th>
                                <th scope="col" class="px-5 py-3 text-right">Escuchas</th>
                                <th scope="col" class="px-5 py-3 text-right">Oyentes</th>
                                <th scope="col" class="px-5 py-3">Escuchado de media</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($episodes as $episode)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-5 py-3">
                                        <a href="{{ route('stats.episode', ['audio' => $episode, 'dias' => $days]) }}" class="font-semibold hover:text-brand-700">{{ $episode->title }}</a>
                                        @if ($episode->show)<p class="text-xs text-gray-500">{{ $episode->show->title }}</p>@endif
                                    </td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $fmt($episode->period_plays) }}</td>
                                    <td class="px-5 py-3 text-right tabular-nums">{{ $fmt($episode->listeners) }}</td>
                                    <td class="px-5 py-3">
                                        @if ($episode->completion !== null)
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-24 overflow-hidden rounded-full bg-brand-100">
                                                    <div class="h-full rounded-full bg-brand-600" style="width: {{ round($episode->completion * 100) }}%"></div>
                                                </div>
                                                <span class="text-xs tabular-nums text-gray-600">{{ round($episode->completion * 100) }} %</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-gray-400">Duración desconocida</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</x-app-layout>
