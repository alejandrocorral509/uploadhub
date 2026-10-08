@php
    $fmt = fn ($n) => number_format($n, 0, ',', '.');
    $points = collect($daily)->map(fn ($d) => [
        'label' => $d['date']->translatedFormat($days > 90 ? 'M' : 'j M'),
        'title' => $d['date']->translatedFormat('l j \d\e F'),
        'value' => $d['plays'],
    ])->all();
@endphp

<x-app-layout :title="'Estadísticas · '.$audio->title">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        <a href="{{ route('stats.index', ['dias' => $days]) }}" class="link text-sm"><i class="fa-solid fa-arrow-left"></i> Todas las estadísticas</a>

        <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
            <div class="flex items-center gap-4">
                <x-cover :url="$audio->coverUrl()" :seed="$audio->id" class="size-16 rounded-xl text-xl" />
                <div>
                    <h1 class="text-2xl font-extrabold tracking-tight">{{ $audio->title }}</h1>
                    <p class="text-sm text-gray-500">
                        <a href="{{ $audio->url() }}" class="link">Ver episodio</a>
                        · {{ $fmt($audio->plays_count) }} escuchas en total · {{ $audio->likes_count }} me gusta · {{ $audio->comments_count }} comentarios
                    </p>
                </div>
            </div>
            <nav class="inline-flex rounded-lg bg-gray-100 p-1 text-sm font-medium" aria-label="Periodo">
                @foreach ($periods as $value => $label)
                    <a href="{{ route('stats.episode', ['audio' => $audio, 'dias' => $value]) }}" @class([
                        'rounded-md px-3 py-1.5',
                        'bg-white text-gray-900 shadow-sm' => $days === $value,
                        'text-gray-500 hover:text-gray-800' => $days !== $value,
                    ])>{{ $label }}</a>
                @endforeach
            </nav>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-3">
            <x-stat-tile label="Escuchas" :value="$fmt($totals['plays'])" />
            <x-stat-tile label="Oyentes únicos" :value="$fmt($totals['listeners'])" />
            <x-stat-tile label="Escuchado de media" :value="$totals['completion'] !== null ? round($totals['completion'] * 100).' %' : '—'" />
        </div>

        <section class="card mt-6 p-5">
            <h2 class="font-bold">Retención</h2>
            <p class="text-sm text-gray-500">Qué porcentaje de las escuchas llega a cada momento del episodio. Las caídas bruscas señalan dónde se va la gente.</p>
            <div class="mt-4">
                @if ($retention)
                    <x-charts.retention :points="$retention" :duration="$audio->duration" />
                @elseif (! $audio->duration)
                    <x-empty-state icon="fa-clock" title="Falta la duración del episodio" text="Se calcula al procesar el audio o la primera vez que alguien lo escucha." />
                @else
                    <x-empty-state icon="fa-chart-line" title="Aún no hay escuchas en este periodo" />
                @endif
            </div>
        </section>

        <section class="card mt-6 p-5">
            <h2 class="font-bold">Escuchas por día</h2>
            <div class="mt-4">
                @if ($totals['plays'] === 0)
                    <p class="text-sm text-gray-500">Sin escuchas en este periodo.</p>
                @else
                    <x-charts.columns :points="$points" />
                @endif
            </div>
        </section>
    </div>
</x-app-layout>
