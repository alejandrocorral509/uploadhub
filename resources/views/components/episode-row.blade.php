@props(['episode', 'resumeAt' => null, 'showAuthor' => true])

<article class="flex items-center gap-4 rounded-xl p-3 transition hover:bg-gray-50">
    <a href="{{ $episode->url() }}" class="shrink-0" tabindex="-1" aria-hidden="true">
        <x-cover :url="$episode->coverUrl()" :seed="$episode->id" class="size-16 rounded-lg text-xl sm:size-20" />
    </a>
    <div class="min-w-0 flex-1">
        <p class="truncate text-xs text-gray-500">
            @if ($episode->episodeLabel())
                <span class="font-semibold text-brand-700">{{ $episode->episodeLabel() }}</span> ·
            @endif
            {{ $episode->created_at->translatedFormat('j M Y') }}
            @if ($episode->formattedDuration())
                · {{ $episode->formattedDuration() }}
            @endif
            @if ($showAuthor)
                · {{ $episode->show?->title ?? $episode->user->name }}
            @endif
        </p>
        <h3 class="truncate font-semibold text-gray-900"><a href="{{ $episode->url() }}" class="hover:text-brand-700">{{ $episode->title }}</a></h3>
        @if ($episode->description)
            <p class="mt-0.5 line-clamp-1 text-sm text-gray-500">{{ $episode->description }}</p>
        @endif
        @if ($resumeAt && $episode->duration)
            <div class="mt-2 h-1 max-w-xs overflow-hidden rounded-full bg-gray-100">
                <div class="h-full bg-brand-500" style="width: {{ min(100, round($resumeAt / $episode->duration * 100)) }}%"></div>
            </div>
        @endif
    </div>
    <div class="flex shrink-0 items-center gap-1">
        @unless ($episode->isPublic())
            <span class="badge badge-gray hidden sm:inline-flex"><i class="fa-solid fa-lock"></i> Privado</span>
        @endunless
        <x-queue-button :episode="$episode" class="hidden sm:inline-flex" />
        <x-play-button :episode="$episode" :resume-at="$resumeAt" />
    </div>
</article>
