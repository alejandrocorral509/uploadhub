@props(['episode', 'resumeAt' => null])

<article class="card group flex flex-col overflow-hidden transition hover:-translate-y-0.5 hover:shadow-md">
    <a href="{{ $episode->url() }}" class="relative block aspect-square overflow-hidden" tabindex="-1" aria-hidden="true">
        <x-cover :url="$episode->coverUrl()" :seed="$episode->id" class="size-full text-4xl transition duration-300 group-hover:scale-105" />
        @if ($episode->formattedDuration())
            <span class="absolute right-2 bottom-2 rounded-md bg-black/70 px-1.5 py-0.5 text-xs font-semibold text-white tabular-nums">{{ $episode->formattedDuration() }}</span>
        @endif
        @if (! $episode->isPublic())
            <span class="badge badge-gray absolute top-2 left-2 bg-white/90"><i class="fa-solid fa-lock"></i> Privado</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-4">
        <p class="truncate text-xs font-medium text-brand-700">
            {{ $episode->show?->title ?? $episode->user->name }}
            @if ($episode->episodeLabel())
                <span class="text-gray-400">· {{ $episode->episodeLabel() }}</span>
            @endif
        </p>
        <h3 class="mt-1 line-clamp-2 font-semibold leading-snug text-gray-900">
            <a href="{{ $episode->url() }}" class="hover:text-brand-700">{{ $episode->title }}</a>
        </h3>
        <p class="mt-1 text-xs text-gray-500">{{ $episode->created_at->translatedFormat('j M Y') }}</p>

        @if ($resumeAt)
            <div class="mt-3 h-1 overflow-hidden rounded-full bg-gray-100" title="Lo dejaste aquí">
                <div class="h-full bg-brand-500" style="width: {{ $episode->duration ? min(100, round($resumeAt / $episode->duration * 100)) : 0 }}%"></div>
            </div>
        @endif

        <div class="mt-auto flex items-center gap-2 pt-4">
            <x-play-button :episode="$episode" :resume-at="$resumeAt" />
            <x-queue-button :episode="$episode" />
            <div class="ml-auto flex items-center gap-3 text-xs text-gray-500">
                @isset($episode->likes_count)
                    <span title="Me gusta"><i class="fa-regular fa-heart"></i> {{ $episode->likes_count }}</span>
                @endisset
                <span title="Reproducciones"><i class="fa-solid fa-headphones"></i> {{ number_format($episode->plays_count, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</article>
