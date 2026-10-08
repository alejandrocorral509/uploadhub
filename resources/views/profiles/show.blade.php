<x-app-layout :title="$profile->name" :description="\Illuminate\Support\Str::limit($profile->bio, 160)">
    <div class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="flex flex-col items-center gap-5 text-center sm:flex-row sm:text-left">
            <x-avatar :user="$profile" size="size-24" class="text-2xl" />
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight">{{ $profile->name }}</h1>
                @if ($profile->bio)
                    <p class="prose-text mt-2 max-w-2xl">{{ $profile->bio }}</p>
                @endif
                <p class="mt-2 text-sm text-gray-500">
                    {{ $stats['episodes'] }} {{ $stats['episodes'] === 1 ? 'episodio' : 'episodios' }} ·
                    {{ number_format($stats['plays'], 0, ',', '.') }} escuchas ·
                    En UploadHub desde {{ $profile->created_at->translatedFormat('F \d\e Y') }}
                </p>
                @if (auth()->id() === $profile->id)
                    <a href="{{ route('profile.edit') }}" class="btn btn-secondary btn-sm mt-3"><i class="fa-solid fa-pen"></i> Editar perfil</a>
                @endif
            </div>
        </div>

        @if ($shows->isNotEmpty())
            <section class="mt-12">
                <h2 class="section-title">Programas</h2>
                <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-4">
                    @foreach ($shows as $show)
                        @php($show->setRelation('user', $profile))
                        <x-show-card :show="$show" />
                    @endforeach
                </div>
            </section>
        @endif

        <section class="mt-12">
            <h2 class="section-title">Episodios</h2>
            @if ($episodes->isEmpty())
                <x-empty-state class="mt-4" icon="fa-podcast" title="Todavía no ha publicado episodios" />
            @else
                <div class="card mt-4 divide-y divide-gray-100 p-2">
                    @foreach ($episodes as $episode)
                        <x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" />
                    @endforeach
                </div>
                <div class="mt-6">{{ $episodes->links() }}</div>
            @endif
        </section>
    </div>
</x-app-layout>
