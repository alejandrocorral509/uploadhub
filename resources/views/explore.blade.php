<x-app-layout :title="request('q') ? 'Buscar: '.request('q') : 'Explorar'">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">
                    @if (request('q'))
                        Resultados para «{{ request('q') }}»
                    @else
                        Explorar episodios
                    @endif
                </h1>
                <p class="mt-1 text-sm text-gray-500">{{ $episodes->total() }} {{ $episodes->total() === 1 ? 'episodio' : 'episodios' }}</p>
            </div>

            <form method="GET" action="{{ route('explore') }}" class="flex w-full flex-wrap gap-2 sm:w-auto">
                @if ($categoryId)
                    <input type="hidden" name="categoria" value="{{ $categoryId }}">
                @endif
                <label for="q" class="sr-only">Buscar</label>
                <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Título, tema, autor o algo que se diga…" class="input flex-1 sm:w-72">
                <label for="orden" class="sr-only">Ordenar</label>
                <select id="orden" name="orden" class="input w-auto" onchange="this.form.requestSubmit()">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-primary"><i class="fa-solid fa-magnifying-glass"></i><span class="sr-only">Buscar</span></button>
            </form>
        </div>

        <nav class="-mx-4 mt-6 flex gap-2 overflow-x-auto px-4 pb-2" aria-label="Categorías">
            <a href="{{ route('explore', request()->except(['categoria', 'page'])) }}" @class(['chip shrink-0', 'chip-active' => ! $categoryId])>Todas</a>
            @foreach ($categories as $category)
                <a href="{{ route('explore', array_merge(request()->except('page'), ['categoria' => $category->id])) }}"
                   @class(['chip shrink-0', 'chip-active' => $categoryId === $category->id])>{{ $category->name }}</a>
            @endforeach
        </nav>

        @if ($episodes->isEmpty())
            <x-empty-state class="mt-8" icon="fa-magnifying-glass" title="No hay episodios con esos filtros"
                           text="Prueba con otra palabra o quita la categoría. También se busca en lo que se dice dentro de los episodios transcritos." />
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($episodes as $episode)
                    <x-episode-card :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                @endforeach
            </div>
            <div class="mt-8">{{ $episodes->links() }}</div>
        @endif
    </div>
</x-app-layout>
