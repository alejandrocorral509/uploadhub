@props(['show'])

<a href="{{ route('shows.show', $show) }}" class="card group block overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="aspect-square overflow-hidden">
        <x-cover :url="$show->coverUrl()" :seed="$show->id" icon="fa-microphone-lines" :alt="$show->title" class="size-full text-5xl transition duration-300 group-hover:scale-105" />
    </div>
    <div class="p-4">
        <h3 class="truncate font-semibold text-gray-900 group-hover:text-brand-700">{{ $show->title }}</h3>
        <p class="truncate text-sm text-gray-500">{{ $show->user->name }}</p>
        <p class="mt-2 flex gap-3 text-xs text-gray-500">
            @isset($show->episodes_count)
                <span><i class="fa-solid fa-podcast"></i> {{ $show->episodes_count }} {{ $show->episodes_count === 1 ? 'episodio' : 'episodios' }}</span>
            @endisset
            @isset($show->subscribers_count)
                <span><i class="fa-solid fa-user-group"></i> {{ $show->subscribers_count }}</span>
            @endisset
        </p>
    </div>
</a>
