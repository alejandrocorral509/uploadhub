<x-app-layout title="Programas">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Programas</h1>
                <p class="mt-1 text-sm text-gray-500">Suscríbete y verás sus episodios nuevos en tu biblioteca.</p>
            </div>
            <form method="GET" class="flex w-full gap-2 sm:w-auto">
                <label for="show-q" class="sr-only">Buscar programas</label>
                <input id="show-q" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar programas…" class="input sm:w-64">
                <select name="categoria" class="input w-auto" onchange="this.form.requestSubmit()" aria-label="Categoría">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request()->integer('categoria') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @auth
                    <a href="{{ route('shows.create') }}" class="btn btn-primary shrink-0"><i class="fa-solid fa-plus"></i> <span class="hidden sm:inline">Crear</span></a>
                @endauth
            </form>
        </div>

        @if ($shows->isEmpty())
            <x-empty-state class="mt-8" icon="fa-microphone-lines" title="No hay programas todavía" text="Un programa agrupa tus episodios y te da un feed RSS para Spotify y Apple Podcasts.">
                <a href="{{ route('shows.create') }}" class="btn btn-primary">Crear el primero</a>
            </x-empty-state>
        @else
            <div class="mt-6 grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                @foreach ($shows as $show)
                    <x-show-card :show="$show" />
                @endforeach
            </div>
            <div class="mt-8">{{ $shows->links() }}</div>
        @endif
    </div>
</x-app-layout>
