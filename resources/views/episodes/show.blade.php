@php
    $payload = $audio->filepath ? $audio->playerPayload($resumeAt) : null;
    $isOwner = auth()->id() === $audio->user_id;
@endphp

<x-app-layout :title="$audio->title" :description="\Illuminate\Support\Str::limit($audio->summary ?? $audio->description, 160)" :image="$audio->coverUrl()">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Avisos para el autor --}}
        @if ($isOwner && in_array($audio->processing_status, ['pending', 'processing'], true))
            <div class="mb-6 flex items-center gap-3 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <i class="fa-solid fa-gear fa-spin"></i>
                Estamos preparando el audio (duración, MP3 y volumen). Ya se puede escuchar; en unos minutos estará optimizado.
            </div>
        @elseif ($isOwner && $audio->processing_status === 'failed')
            <div class="mb-6 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="fa-solid fa-triangle-exclamation"></i>
                No hemos podido procesar este archivo. Puede que esté dañado: prueba a subirlo de nuevo desde «Editar».
            </div>
        @endif

        <div class="flex flex-col gap-6 sm:flex-row">
            <x-cover :url="$audio->coverUrl()" :seed="$audio->id" :alt="'Portada de '.$audio->title" class="size-48 shrink-0 rounded-2xl text-6xl shadow-lg sm:size-56" />

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-brand-700">
                    @if ($audio->show)
                        <a href="{{ route('shows.show', $audio->show) }}" class="hover:underline">{{ $audio->show->title }}</a>
                    @else
                        Episodio
                    @endif
                    @if ($audio->episodeLabel())
                        <span class="text-gray-400">· {{ $audio->episodeLabel() }}</span>
                    @endif
                </p>
                <h1 class="mt-1 text-2xl font-extrabold tracking-tight text-gray-900 sm:text-3xl">{{ $audio->title }}</h1>
                <a href="{{ $audio->user->profileUrl() }}" class="mt-3 inline-flex items-center gap-2 text-sm text-gray-600 hover:text-brand-700">
                    <x-avatar :user="$audio->user" size="size-7" /> {{ $audio->user->name }}
                </a>
                <p class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-gray-500">
                    <span>{{ $audio->created_at->translatedFormat('j \d\e F \d\e Y') }}</span>
                    @if ($audio->formattedDuration())
                        <span>· {{ $audio->formattedDuration() }}</span>
                    @endif
                    <span>· {{ number_format($audio->plays_count, 0, ',', '.') }} {{ $audio->plays_count === 1 ? 'escucha' : 'escuchas' }}</span>
                    @unless ($audio->isPublic())
                        <span class="badge badge-gray"><i class="fa-solid fa-lock"></i> Privado: solo lo ves tú</span>
                    @endunless
                </p>

                <div class="mt-5 flex flex-wrap items-center gap-2">
                    @if ($payload && ! $audio->isVideo())
                        <x-play-button :episode="$audio" :resume-at="$resumeAt" label />
                        <x-queue-button :episode="$audio" class="btn-icon size-11 border border-gray-300 bg-white" />
                    @endif

                    @auth
                        <button type="button" x-data="likeButton(@js(route('likes.toggle', $audio)), @js($liked), {{ $audio->likes_count }})" @click="toggle"
                                class="btn btn-secondary" :class="liked && 'border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100'" :aria-pressed="liked">
                            <i :class="liked ? 'fa-solid fa-heart' : 'fa-regular fa-heart'"></i> <span x-text="count"></span>
                        </button>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary" title="Inicia sesión para darle a me gusta"><i class="fa-regular fa-heart"></i> {{ $audio->likes_count }}</a>
                    @endauth

                    <div x-data="shareButton(@js($audio->url()), @js($audio->title))" class="relative" @click.outside="menu = false">
                        <button type="button" @click="menu = ! menu" class="btn btn-secondary"><i class="fa-solid fa-share-nodes"></i> Compartir</button>
                        <div x-show="menu" x-cloak x-transition class="absolute left-0 z-20 mt-2 w-48 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 text-sm shadow-lg">
                            <button type="button" @click="share(); menu = false" class="flex w-full items-center gap-3 px-4 py-2 hover:bg-gray-50"><i class="fa-solid fa-link w-4 text-gray-400"></i> Copiar enlace</button>
                            <button type="button" @click="whatsapp(); menu = false" class="flex w-full items-center gap-3 px-4 py-2 hover:bg-gray-50"><i class="fa-brands fa-whatsapp w-4 text-gray-400"></i> WhatsApp</button>
                            <button type="button" @click="email(); menu = false" class="flex w-full items-center gap-3 px-4 py-2 hover:bg-gray-50"><i class="fa-solid fa-envelope w-4 text-gray-400"></i> Correo</button>
                        </div>
                    </div>

                    @if ($audio->filepath)
                        <a href="{{ route('audios.download', $audio) }}" data-turbo="false" class="btn btn-secondary" title="Descargar"><i class="fa-solid fa-download"></i><span class="sr-only">Descargar</span></a>
                    @endif
                </div>

                @if ($isOwner)
                    <div class="mt-4 flex flex-wrap gap-2 border-t border-gray-100 pt-4">
                        <a href="{{ route('episodes.edit', $audio) }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-pen"></i> Editar</a>
                        <a href="{{ route('stats.episode', $audio) }}" class="btn btn-ghost btn-sm"><i class="fa-solid fa-chart-column"></i> Estadísticas</a>
                        <form method="POST" action="{{ route('episodes.destroy', $audio) }}"
                              x-data="confirmForm({ title: '¿Eliminar este episodio?', text: 'Se borrarán también el archivo, los comentarios y las estadísticas. No se puede deshacer.', confirm: 'Sí, eliminar', danger: true })" @submit="submit">
                            @csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50 hover:text-red-700"><i class="fa-solid fa-trash"></i> Eliminar</button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        {{-- Contenido: YouTube, vídeo o forma de onda --}}
        @if ($audio->isYoutube() && $audio->youtubeId())
            <div class="mt-8 aspect-video overflow-hidden rounded-2xl bg-black shadow-lg">
                <iframe src="{{ \App\Support\YouTube::embedUrl($audio->youtubeId()) }}" title="{{ $audio->title }}" class="size-full"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen loading="lazy"></iframe>
            </div>
        @elseif ($audio->isVideo() && $audio->filepath)
            <div class="mt-8 overflow-hidden rounded-2xl bg-black shadow-lg" x-data="videoTracker(@js(route('plays.store', $audio)))">
                <video controls preload="metadata" class="max-h-[70vh] w-full" src="{{ route('audios.stream', $audio) }}" @play="onPlay" @timeupdate="onTime"></video>
            </div>
        @elseif ($payload && $audio->waveform)
            <div class="mt-8" x-data="waveform(@js($payload))">
                <button type="button" @click="seekTo($event)" class="relative flex h-20 w-full cursor-pointer items-center gap-[2px]" aria-label="Forma de onda: haz clic para saltar a ese punto">
                    @foreach ($audio->waveform as $i => $peak)
                        <span class="flex-1 rounded-full transition-colors"
                              :class="{{ $i / count($audio->waveform) }} < progress ? 'bg-brand-600' : 'bg-gray-200'"
                              style="height: {{ max(6, $peak) }}%"></span>
                    @endforeach
                </button>
            </div>
        @endif

        <div class="mt-10 grid gap-10 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                @if ($audio->summary)
                    <section class="rounded-2xl bg-brand-50/60 p-5">
                        <h2 class="flex items-center gap-2 text-sm font-bold text-brand-800"><i class="fa-solid fa-wand-magic-sparkles"></i> Resumen</h2>
                        <p class="prose-text mt-2">{{ $audio->summary }}</p>
                    </section>
                @endif

                @if ($audio->description)
                    <section>
                        <h2 class="section-title">Descripción</h2>
                        <div class="prose-text mt-3">{!! nl2br(e($audio->description)) !!}</div>
                    </section>
                @endif

                @if ($audio->chapters && $payload)
                    <section>
                        <h2 class="section-title">Capítulos</h2>
                        <ol class="card mt-3 divide-y divide-gray-100">
                            @foreach ($audio->chapters as $chapter)
                                <li>
                                    <button type="button" x-data @click="$store.player.playAt(@js($payload), {{ (int) $chapter['start'] }})"
                                            class="flex w-full items-center gap-4 px-4 py-3 text-left hover:bg-gray-50">
                                        <span class="w-14 text-sm font-semibold text-brand-700 tabular-nums">{{ gmdate($chapter['start'] >= 3600 ? 'G:i:s' : 'i:s', (int) $chapter['start']) }}</span>
                                        <span class="text-sm text-gray-800">{{ $chapter['title'] }}</span>
                                    </button>
                                </li>
                            @endforeach
                        </ol>
                    </section>
                @endif

                @if ($audio->transcript)
                    <section x-data="transcript">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <h2 class="section-title">Transcripción</h2>
                            <input type="search" x-model="query" @input="open = true" placeholder="Buscar en la transcripción…" class="input w-full sm:w-64" aria-label="Buscar en la transcripción">
                        </div>
                        @php($paragraphs = collect(preg_split('/(?<=[.!?])\s+/u', $audio->transcript))->chunk(5)->map->implode(' '))
                        <div class="card relative mt-3 p-5" :class="! open && 'max-h-64 overflow-hidden'">
                            @foreach ($paragraphs as $paragraph)
                                <p class="prose-text mb-3" x-show="matches(@js($paragraph))" x-html="highlight(@js($paragraph))">{{ $paragraph }}</p>
                            @endforeach
                            <div x-show="! open" class="absolute inset-x-0 bottom-0 flex h-24 items-end justify-center rounded-b-2xl bg-gradient-to-t from-white to-transparent pb-4">
                                <button type="button" @click="open = true" class="btn btn-secondary btn-sm">Leer la transcripción completa</button>
                            </div>
                        </div>
                    </section>
                @elseif ($isOwner && $audio->transcript_status === 'processing')
                    <p class="text-sm text-gray-500"><i class="fa-solid fa-spinner fa-spin"></i> Generando la transcripción y el resumen…</p>
                @endif

                {{-- Comentarios --}}
                <section id="comentarios">
                    <h2 class="section-title">Comentarios <span class="text-gray-400">{{ $audio->comments_count }}</span></h2>

                    @auth
                        <form method="POST" action="{{ route('comments.store', $audio) }}" class="mt-4 flex gap-3">
                            @csrf
                            <x-avatar :user="auth()->user()" size="size-9" />
                            <div class="flex-1">
                                <label for="comment" class="sr-only">Tu comentario</label>
                                <textarea id="comment" name="content" rows="2" maxlength="1000" required placeholder="¿Qué te ha parecido?" class="input">{{ old('content') }}</textarea>
                                @error('content') <p class="field-error">{{ $message }}</p> @enderror
                                <button class="btn btn-primary btn-sm mt-2">Comentar</button>
                            </div>
                        </form>
                    @else
                        <p class="mt-4 rounded-xl bg-gray-100 px-4 py-3 text-sm text-gray-600">
                            <a href="{{ route('login') }}" class="link">Inicia sesión</a> para comentar.
                        </p>
                    @endauth

                    <ul class="mt-6 space-y-5">
                        @forelse ($comments as $comment)
                            <li class="flex gap-3">
                                <a href="{{ $comment->user->profileUrl() }}"><x-avatar :user="$comment->user" size="size-9" /></a>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm">
                                        <a href="{{ $comment->user->profileUrl() }}" class="font-semibold hover:text-brand-700">{{ $comment->user->name }}</a>
                                        @if ($comment->user_id === $audio->user_id)
                                            <span class="badge badge-brand ml-1">Autor</span>
                                        @endif
                                        <span class="text-gray-400">· {{ $comment->created_at->diffForHumans() }}</span>
                                    </p>
                                    <p class="prose-text mt-1 break-words">{!! nl2br(e($comment->content)) !!}</p>
                                    <div class="mt-1 flex gap-4">
                                        @can('delete', $comment)
                                            <form method="POST" action="{{ route('comments.destroy', $comment) }}"
                                                  x-data="confirmForm({ title: '¿Borrar el comentario?', confirm: 'Borrar', danger: true })" @submit="submit">
                                                @csrf @method('DELETE')
                                                <button class="text-xs text-gray-400 hover:text-red-600"><i class="fa-regular fa-trash-can"></i> Borrar</button>
                                            </form>
                                        @endcan
                                        @if (auth()->check() && auth()->id() !== $comment->user_id)
                                            <x-report-button type="comment" :id="$comment->id" :reasons="$reportReasons" />
                                        @endif
                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">Todavía no hay comentarios. ¡Escribe el primero!</li>
                        @endforelse
                    </ul>
                    <div class="mt-6">{{ $comments->fragment('comentarios')->links() }}</div>
                </section>
            </div>

            <aside class="space-y-8">
                @if ($audio->categories->isNotEmpty() || $audio->tags->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-bold text-gray-900">Temas</h2>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($audio->categories as $category)
                                <a href="{{ route('explore', ['categoria' => $category->id]) }}" class="badge badge-brand">{{ $category->name }}</a>
                            @endforeach
                            @foreach ($audio->tags as $tag)
                                <a href="{{ route('explore', ['q' => $tag->name]) }}" class="badge badge-gray">#{{ $tag->name }}</a>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($moreFromShow->isNotEmpty())
                    <section>
                        <h2 class="text-sm font-bold text-gray-900">Más de {{ $audio->show->title }}</h2>
                        <div class="mt-2 -mx-3">
                            @foreach ($moreFromShow as $episode)
                                <x-episode-row :episode="$episode" :show-author="false" />
                            @endforeach
                        </div>
                    </section>
                @endif

                @if (auth()->check() && ! $isOwner)
                    <x-report-button type="audio" :id="$audio->id" :reasons="$reportReasons" label="Denunciar este episodio" />
                @endif
            </aside>
        </div>
    </div>
</x-app-layout>
