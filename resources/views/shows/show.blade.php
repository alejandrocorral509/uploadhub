@php($isOwner = auth()->id() === $show->user_id)

<x-app-layout :title="$show->title" :description="\Illuminate\Support\Str::limit($show->description, 160)" :image="$show->coverUrl()">
    <x-slot:head>
        <link rel="alternate" type="application/rss+xml" title="{{ $show->title }}" href="{{ $show->feedUrl() }}">
    </x-slot:head>

    <div class="bg-gradient-to-b from-brand-50 to-gray-50">
        <div class="mx-auto flex max-w-5xl flex-col gap-6 px-4 py-10 sm:flex-row sm:px-6 lg:px-8">
            <x-cover :url="$show->coverUrl()" :seed="$show->id" icon="fa-microphone-lines" :alt="'Portada de '.$show->title" class="size-48 shrink-0 rounded-2xl text-6xl shadow-lg sm:size-56" />
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-brand-700">Programa{{ $show->category ? ' · '.$show->category->name : '' }}{{ $show->explicit ? ' · Explícito' : '' }}</p>
                <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-gray-900">{{ $show->title }}</h1>
                <a href="{{ $show->user->profileUrl() }}" class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-700">
                    <x-avatar :user="$show->user" size="size-7" /> {{ $show->user->name }}
                </a>
                @if ($show->description)
                    <p class="prose-text mt-4 max-w-2xl">{!! nl2br(e($show->description)) !!}</p>
                @endif

                <div class="mt-6 flex flex-wrap items-center gap-2">
                    @if ($episodes->isNotEmpty() && $episodes->first()->filepath)
                        <x-play-button :episode="$episodes->first()" :resume-at="$positions[$episodes->first()->id] ?? null" label />
                    @endif

                    @auth
                        @unless ($isOwner)
                            <button type="button" x-data="subscribeButton(@js(route('shows.subscribe', $show)), @js($subscribed), {{ $show->subscribers_count }})" @click="toggle"
                                    class="btn btn-lg" :class="subscribed ? 'btn-secondary' : 'btn-primary'">
                                <i class="fa-solid" :class="subscribed ? 'fa-check' : 'fa-plus'"></i>
                                <span x-text="subscribed ? 'Suscrito' : 'Suscribirse'"></span>
                                <span class="text-xs opacity-70" x-text="'· ' + count"></span>
                            </button>
                        @endunless
                    @else
                        <a href="{{ route('login') }}" class="btn btn-primary btn-lg"><i class="fa-solid fa-plus"></i> Suscribirse · {{ $show->subscribers_count }}</a>
                    @endauth

                    <div x-data="shareButton(@js($show->feedUrl()), @js($show->title))">
                        <button type="button" @click="copy(undefined, 'Enlace del feed RSS copiado. Pégalo en tu app de podcasts.')" class="btn btn-secondary" title="Copiar el enlace del feed RSS">
                            <i class="fa-solid fa-rss text-orange-500"></i> Feed RSS
                        </button>
                    </div>
                </div>

                @if ($isOwner)
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-brand-100 pt-4">
                        <a href="{{ route('episodes.create', ['programa' => $show->slug]) }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Subir episodio</a>
                        <a href="{{ route('shows.edit', $show) }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-pen"></i> Editar programa</a>
                        <form method="POST" action="{{ route('shows.destroy', $show) }}"
                              x-data="confirmForm({ title: '¿Eliminar el programa?', text: 'Sus episodios no se borran: quedan como episodios sueltos.', confirm: 'Eliminar', danger: true })" @submit="submit">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50"><i class="fa-solid fa-trash"></i> Eliminar</button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-gray-500">
                        <i class="fa-solid fa-circle-info"></i> Para salir en Spotify o Apple Podcasts, envía este feed desde su panel para creadores:
                        <code class="rounded bg-white px-1.5 py-0.5 text-gray-700">{{ $show->feedUrl() }}</code>
                    </p>
                @endif
            </div>
        </div>
    </div>

    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <h2 class="section-title">Episodios <span class="text-gray-400">{{ $episodes->total() }}</span></h2>
        @if ($episodes->isEmpty())
            <x-empty-state class="mt-4" icon="fa-podcast" title="Este programa aún no tiene episodios">
                @if ($isOwner)
                    <a href="{{ route('episodes.create', ['programa' => $show->slug]) }}" class="btn btn-primary">Subir el primero</a>
                @endif
            </x-empty-state>
        @else
            <div class="card mt-4 divide-y divide-gray-100 p-2">
                @foreach ($episodes as $episode)
                    <x-episode-row :episode="$episode" :resume-at="$positions[$episode->id] ?? null" :show-author="false" />
                @endforeach
            </div>
            <div class="mt-6">{{ $episodes->links() }}</div>
        @endif

        @if (auth()->check() && ! $isOwner)
            <div class="mt-8"><x-report-button type="show" :id="$show->id" :reasons="$reportReasons" label="Denunciar este programa" /></div>
        @endif
    </div>
</x-app-layout>
