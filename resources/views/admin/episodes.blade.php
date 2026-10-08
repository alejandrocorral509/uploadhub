<x-app-layout title="Episodios">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.nav')

        <form method="GET" class="mt-6 flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar episodios…" class="input max-w-sm" aria-label="Buscar episodios">
            <select name="estado" class="input w-auto" onchange="this.form.requestSubmit()" aria-label="Estado">
                <option value="">Todos</option>
                <option value="fallidos" @selected(request('estado') === 'fallidos')>Con error al procesar</option>
            </select>
            <button class="btn btn-secondary">Buscar</button>
        </form>

        <div class="card mt-4 divide-y divide-gray-100">
            @forelse ($episodes as $episode)
                <div class="flex items-center gap-4 p-4">
                    <x-cover :url="$episode->coverUrl()" :seed="$episode->id" class="size-12 shrink-0 rounded-lg" />
                    <div class="min-w-0 flex-1">
                        <a href="{{ $episode->url() }}" class="block truncate font-semibold hover:text-brand-700">{{ $episode->title }}</a>
                        <p class="text-xs text-gray-500">
                            {{ $episode->user->name }} · {{ $episode->created_at->diffForHumans() }} · {{ $episode->privacy === 'public' ? 'Público' : 'Privado' }}
                            · {{ $episode->comments_count }} comentarios
                            @if ($episode->reports_count) · <span class="font-semibold text-red-600">{{ $episode->reports_count }} denuncias</span> @endif
                            @if ($episode->processing_status === 'failed') · <span class="font-semibold text-red-600">Error al procesar</span> @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('episodes.destroy', $episode) }}"
                          x-data="confirmForm({ title: @js('¿Eliminar «'.$episode->title.'»?'), confirm: 'Eliminar', danger: true })" @submit="submit">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50"><i class="fa-solid fa-trash"></i> Eliminar</button>
                    </form>
                </div>
            @empty
                <p class="p-6 text-center text-sm text-gray-500">No hay episodios.</p>
            @endforelse
        </div>
        <div class="mt-6">{{ $episodes->links() }}</div>
    </div>
</x-app-layout>
