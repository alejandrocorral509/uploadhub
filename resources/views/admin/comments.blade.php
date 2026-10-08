<x-app-layout title="Comentarios">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.nav')

        <form method="GET" class="mt-6 flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Buscar en los comentarios…" class="input max-w-sm" aria-label="Buscar comentarios">
            <button class="btn btn-secondary">Buscar</button>
        </form>

        <ul class="card mt-4 divide-y divide-gray-100">
            @forelse ($comments as $comment)
                <li class="flex items-start gap-4 p-4">
                    <x-avatar :user="$comment->user" size="size-8" />
                    <div class="min-w-0 flex-1">
                        <p class="text-xs text-gray-500">
                            <span class="font-semibold text-gray-800">{{ $comment->user->name }}</span>
                            en <a href="{{ $comment->audio->url() }}#comentarios" class="link">{{ $comment->audio->title }}</a>
                            · {{ $comment->created_at->diffForHumans() }}
                            @if ($comment->reports_count) · <span class="font-semibold text-red-600">{{ $comment->reports_count }} denuncias</span> @endif
                        </p>
                        <p class="mt-1 text-sm break-words">{{ $comment->content }}</p>
                    </div>
                    <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                          x-data="confirmForm({ title: '¿Borrar el comentario?', confirm: 'Borrar', danger: true })" @submit="submit">
                        @csrf @method('DELETE')
                        <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </li>
            @empty
                <li class="p-6 text-center text-sm text-gray-500">No hay comentarios.</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $comments->links() }}</div>
    </div>
</x-app-layout>
