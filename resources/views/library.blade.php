<x-app-layout title="Tu biblioteca">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Tu biblioteca</h1>
            <div class="flex gap-2">
                <a href="{{ route('shows.create') }}" class="btn btn-secondary"><i class="fa-solid fa-plus"></i> Programa</a>
                <a href="{{ route('episodes.create') }}" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Subir episodio</a>
            </div>
        </div>

        <dl class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Episodios', $summary['episodes'], 'fa-podcast'],
                ['Escuchas', $summary['plays'], 'fa-headphones'],
                ['Me gusta recibidos', $summary['likes'], 'fa-heart'],
                ['Suscriptores', $summary['subscribers'], 'fa-user-group'],
            ] as [$label, $value, $icon])
                <div class="card flex items-center gap-3 p-4">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid {{ $icon }}"></i></span>
                    <div>
                        <dt class="text-xs text-gray-500">{{ $label }}</dt>
                        <dd class="text-xl font-bold tabular-nums">{{ number_format($value, 0, ',', '.') }}</dd>
                    </div>
                </div>
            @endforeach
        </dl>

        <nav class="-mx-4 mt-8 flex gap-1 overflow-x-auto border-b border-gray-200 px-4" aria-label="Secciones de la biblioteca">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('library', ['tab' => $key]) }}" @class([
                    '-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold transition',
                    'border-brand-600 text-brand-700' => $tab === $key,
                    'border-transparent text-gray-500 hover:text-gray-800' => $tab !== $key,
                ]) @if ($tab === $key) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>

        <div class="mt-6">
            @if ($tab === 'episodios')
                <form method="GET" class="flex flex-wrap gap-2">
                    <input type="hidden" name="tab" value="episodios">
                    <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar en tus episodios…" class="input min-w-48 flex-1" aria-label="Buscar en tus episodios">
                    <select name="categoria" class="input w-auto" onchange="this.form.requestSubmit()" aria-label="Categoría">
                        <option value="">Todas las categorías</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request()->integer('categoria') === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select name="privacidad" class="input w-auto" onchange="this.form.requestSubmit()" aria-label="Visibilidad">
                        <option value="">Públicos y privados</option>
                        <option value="public" @selected(request('privacidad') === 'public')>Solo públicos</option>
                        <option value="private" @selected(request('privacidad') === 'private')>Solo privados</option>
                    </select>
                    <button class="btn btn-secondary">Filtrar</button>
                </form>

                @if ($episodes->isEmpty())
                    <x-empty-state class="mt-6" icon="fa-cloud-arrow-up" title="{{ request()->hasAny(['q', 'categoria', 'privacidad']) ? 'Ningún episodio coincide' : 'Aún no has subido episodios' }}">
                        <a href="{{ route('episodes.create') }}" class="btn btn-primary">Subir un episodio</a>
                    </x-empty-state>
                @else
                    <div class="card mt-4 overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                                <tr>
                                    @foreach (['title' => 'Episodio', 'created_at' => 'Fecha', 'plays_count' => 'Escuchas', 'likes_count' => 'Me gusta'] as $column => $label)
                                        @php($next = $sort === $column && $direction === 'desc' ? 'asc' : 'desc')
                                        <th scope="col" class="px-4 py-3 {{ $column === 'title' ? '' : 'text-right' }}" @if ($sort === $column) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif>
                                            <a href="{{ route('library', array_merge(request()->except('page'), ['orden' => $column, 'dir' => $next])) }}" class="inline-flex items-center gap-1 hover:text-gray-900">
                                                {{ $label }}
                                                @if ($sort === $column)
                                                    <i class="fa-solid {{ $direction === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down' }}"></i>
                                                @endif
                                            </a>
                                        </th>
                                    @endforeach
                                    <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($episodes as $episode)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <x-cover :url="$episode->coverUrl()" :seed="$episode->id" class="size-11 shrink-0 rounded-lg text-sm" />
                                                <div class="min-w-0">
                                                    <a href="{{ $episode->url() }}" class="block max-w-xs truncate font-semibold hover:text-brand-700">{{ $episode->title }}</a>
                                                    <div class="mt-0.5 flex flex-wrap items-center gap-1 text-xs text-gray-500">
                                                        @if ($episode->show)<span>{{ $episode->show->title }} ·</span>@endif
                                                        @foreach ($episode->categories as $category)
                                                            <span class="badge badge-brand">{{ $category->name }}</span>
                                                        @endforeach
                                                        @unless ($episode->isPublic())
                                                            <span class="badge badge-gray"><i class="fa-solid fa-lock"></i> Privado</span>
                                                        @endunless
                                                        @if ($episode->processing_status === 'failed')
                                                            <span class="badge badge-red">Error al procesar</span>
                                                        @elseif (in_array($episode->processing_status, ['pending', 'processing'], true))
                                                            <span class="badge badge-amber">Procesando</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-right whitespace-nowrap text-gray-500">{{ $episode->created_at->translatedFormat('j M Y') }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($episode->plays_count, 0, ',', '.') }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ $episode->likes_count }}</td>
                                        <td class="px-4 py-3">
                                            <div class="flex justify-end gap-1">
                                                <x-play-button :episode="$episode" size="size-9" />
                                                <a href="{{ route('stats.episode', $episode) }}" class="btn-icon size-9 text-gray-500 hover:bg-gray-100" title="Estadísticas"><i class="fa-solid fa-chart-column"></i></a>
                                                <a href="{{ route('episodes.edit', $episode) }}" class="btn-icon size-9 text-gray-500 hover:bg-gray-100" title="Editar"><i class="fa-solid fa-pen"></i></a>
                                                <form method="POST" action="{{ route('episodes.destroy', $episode) }}"
                                                      x-data="confirmForm({ title: @js('¿Eliminar «'.$episode->title.'»?'), text: 'No se puede deshacer.', confirm: 'Eliminar', danger: true })" @submit="submit">
                                                    @csrf @method('DELETE')
                                                    <button class="btn-icon size-9 text-gray-500 hover:bg-red-50 hover:text-red-600" title="Eliminar"><i class="fa-solid fa-trash"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-6">{{ $episodes->links() }}</div>
                @endif

            @elseif ($tab === 'programas')
                @if ($shows->isEmpty())
                    <x-empty-state icon="fa-microphone-lines" title="Aún no tienes programas" text="Agrupa tus episodios en un programa y consigue un feed RSS para publicarlo en Spotify y Apple Podcasts.">
                        <a href="{{ route('shows.create') }}" class="btn btn-primary">Crear un programa</a>
                    </x-empty-state>
                @else
                    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                        @foreach ($shows as $show)
                            @php($show->setRelation('user', auth()->user()))
                            <x-show-card :show="$show" />
                        @endforeach
                    </div>
                @endif

            @elseif ($tab === 'suscripciones')
                @if ($subscriptions->isEmpty())
                    <x-empty-state icon="fa-user-group" title="No sigues ningún programa" text="Suscríbete a un programa y sus episodios nuevos aparecerán aquí.">
                        <a href="{{ route('shows.index') }}" class="btn btn-primary">Ver programas</a>
                    </x-empty-state>
                @else
                    <div class="-mx-4 flex gap-3 overflow-x-auto px-4 pb-2">
                        @foreach ($subscriptions as $show)
                            <a href="{{ route('shows.show', $show) }}" class="w-28 shrink-0 text-center">
                                <x-cover :url="$show->coverUrl()" :seed="$show->id" icon="fa-microphone-lines" class="size-28 rounded-xl text-3xl" />
                                <p class="mt-1 truncate text-xs font-semibold">{{ $show->title }}</p>
                            </a>
                        @endforeach
                    </div>
                    <h2 class="section-title mt-8">Últimos episodios</h2>
                    <div class="card mt-4 divide-y divide-gray-100 p-2">
                        @forelse ($episodes as $episode)
                            <x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                        @empty
                            <p class="p-4 text-sm text-gray-500">Tus programas aún no han publicado episodios.</p>
                        @endforelse
                    </div>
                    <div class="mt-6">{{ $episodes->links() }}</div>
                @endif

            @else
                @if ($episodes->isEmpty())
                    @if ($tab === 'favoritos')
                        <x-empty-state icon="fa-heart" title="Aún no tienes favoritos" text="Dale a me gusta a un episodio y lo encontrarás aquí.">
                            <a href="{{ route('explore') }}" class="btn btn-primary">Explorar</a>
                        </x-empty-state>
                    @else
                        <x-empty-state icon="fa-headphones" title="No tienes nada a medias" text="Los episodios que empieces y no termines aparecerán aquí para seguir donde lo dejaste." />
                    @endif
                @else
                    <div class="card divide-y divide-gray-100 p-2">
                        @foreach ($episodes as $episode)
                            <x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                        @endforeach
                    </div>
                    <div class="mt-6">{{ $episodes->links() }}</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
