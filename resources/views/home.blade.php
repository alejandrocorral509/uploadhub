<x-app-layout>
    {{-- Portada --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-brand-950 via-brand-800 to-fuchsia-800 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-20" aria-hidden="true"
             style="background-image: radial-gradient(circle at 20% 20%, white 1px, transparent 1px); background-size: 28px 28px;"></div>
        <div class="relative mx-auto grid max-w-7xl items-center gap-10 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8 lg:py-24">
            <div>
                <p class="badge bg-white/10 text-white ring-1 ring-white/20"><i class="fa-solid fa-wand-magic-sparkles"></i> Transcripción y capítulos automáticos</p>
                <h1 class="mt-4 text-4xl font-extrabold tracking-tight sm:text-5xl">Tu podcast, en todas partes.</h1>
                <p class="mt-4 max-w-lg text-lg text-brand-100">
                    Sube tus episodios, publícalos en Spotify y Apple Podcasts con tu feed RSS y descubre qué escucha tu audiencia y en qué minuto se va.
                </p>
                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('episodes.create') }}" class="btn btn-lg bg-white text-brand-800 hover:bg-brand-50"><i class="fa-solid fa-cloud-arrow-up"></i> Subir un episodio</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-lg bg-white text-brand-800 hover:bg-brand-50">Crear cuenta gratis</a>
                    @endauth
                    <a href="{{ route('explore') }}" class="btn btn-lg bg-white/10 text-white ring-1 ring-white/30 hover:bg-white/20"><i class="fa-solid fa-headphones"></i> Empezar a escuchar</a>
                </div>
                <dl class="mt-10 flex gap-8 text-sm">
                    <div><dt class="text-brand-200">Episodios</dt><dd class="text-2xl font-bold">{{ number_format($stats['episodes'], 0, ',', '.') }}</dd></div>
                    <div><dt class="text-brand-200">Programas</dt><dd class="text-2xl font-bold">{{ number_format($stats['shows'], 0, ',', '.') }}</dd></div>
                    <div><dt class="text-brand-200">Escuchas</dt><dd class="text-2xl font-bold">{{ number_format($stats['plays'], 0, ',', '.') }}</dd></div>
                </dl>
            </div>

            <ul class="hidden gap-4 lg:grid lg:grid-cols-2" aria-label="Cómo funciona">
                @foreach ([
                    ['fa-cloud-arrow-up', 'Sube sin límites raros', 'Archivos de hasta 500 MB, con barra de progreso y reintentos si se corta la conexión.'],
                    ['fa-rss', 'Feed RSS por programa', 'Pega el enlace en Spotify o Apple Podcasts y tus episodios aparecerán solos.'],
                    ['fa-wave-square', 'Audio listo para escuchar', 'Convertimos a MP3, igualamos el volumen y dibujamos la forma de onda.'],
                    ['fa-chart-line', 'Estadísticas de verdad', 'Escuchas, oyentes únicos y la curva de retención de cada episodio.'],
                ] as [$icon, $title, $text])
                    <li class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                        <i class="fa-solid {{ $icon }} text-xl text-fuchsia-300"></i>
                        <h2 class="mt-3 font-semibold">{{ $title }}</h2>
                        <p class="mt-1 text-sm text-brand-100">{{ $text }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <div class="mx-auto max-w-7xl space-y-14 px-4 py-12 sm:px-6 lg:px-8">
        @if ($continue->isNotEmpty())
            <section>
                <h2 class="section-title">Continuar escuchando</h2>
                <div class="mt-4 grid gap-2 sm:grid-cols-2">
                    @foreach ($continue as $episode)
                        <div class="card"><x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" /></div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($categories->where('audios_count', '>', 0)->isNotEmpty())
            <section aria-label="Categorías">
                <div class="flex flex-wrap gap-2">
                    @foreach ($categories->where('audios_count', '>', 0) as $category)
                        <a href="{{ route('explore', ['categoria' => $category->id]) }}" class="chip">{{ $category->name }} <span class="text-gray-400">{{ $category->audios_count }}</span></a>
                    @endforeach
                </div>
            </section>
        @endif

        <section>
            <div class="flex items-end justify-between">
                <h2 class="section-title">Lo más escuchado</h2>
                <a href="{{ route('explore', ['orden' => 'escuchados']) }}" class="link text-sm">Ver todo</a>
            </div>
            @if ($popular->isEmpty())
                <x-empty-state class="mt-4" icon="fa-headphones" title="Todavía no hay episodios" text="Sé la primera persona en publicar uno.">
                    <a href="{{ route('episodes.create') }}" class="btn btn-primary">Subir un episodio</a>
                </x-empty-state>
            @else
                <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    @foreach ($popular as $episode)
                        <x-episode-card :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                    @endforeach
                </div>
            @endif
        </section>

        @if ($shows->isNotEmpty())
            <section>
                <div class="flex items-end justify-between">
                    <h2 class="section-title">Programas destacados</h2>
                    <a href="{{ route('shows.index') }}" class="link text-sm">Ver todos</a>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
                    @foreach ($shows as $show)
                        <x-show-card :show="$show" />
                    @endforeach
                </div>
            </section>
        @endif

        @if ($latest->isNotEmpty())
            <section>
                <div class="flex items-end justify-between">
                    <h2 class="section-title">Recién publicados</h2>
                    <a href="{{ route('explore') }}" class="link text-sm">Ver todo</a>
                </div>
                <div class="card mt-4 divide-y divide-gray-100 p-2">
                    @foreach ($latest as $episode)
                        <x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
