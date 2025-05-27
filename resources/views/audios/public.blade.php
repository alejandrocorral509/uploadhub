<x-app-layout>
    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 bg-white rounded-lg shadow-md p-6">

            {{-- Buscador y filtro por categoría --}}
            <form method="GET" action="" class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="relative flex-grow">
                    <input 
                        type="text" 
                        name="search" 
                        value="{{ request('search') }}" 
                        placeholder="Buscar archivos..." 
                        class="w-full border border-gray-300 rounded pl-10 pr-3 py-2 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                    <svg class="w-5 h-5 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2 pointer-events-none" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 1110.5 3a7.5 7.5 0 016.15 13.65z" />
                    </svg>
                </div>

                <select 
                    name="category" 
                    class="border border-gray-300 rounded px-2 py-2 pr-2 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 transition">
                    Filtrar
                </button>
            </form>

            @if($audios->count())
                <div class="space-y-5">
                    @foreach($audios as $audio)
                        <article class="border border-gray-200 rounded-md p-4 hover:shadow-md transition-shadow duration-150">
                            <header class="mb-1">
                                <h2 class="font-semibold text-lg text-gray-800 truncate">{{ $audio->titulo }}</h2>
                                <p class="text-xs text-gray-500">Subido por {{ $audio->user->name }}</p>
                            </header>

                            <p class="text-gray-700 mb-3 line-clamp-3">{{ $audio->description }}</p>

                            <div class="flex flex-wrap gap-1 mb-3">
                                @foreach($audio->categories as $category)
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                                @foreach($audio->tags as $tag)
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-2 py-0.5 rounded-full">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>

                            @if (Str::startsWith($audio->filetype, 'audio'))
                                <div class="mb-3 flex items-center gap-4 select-none">
                                    <button
                                        type="button"
                                        class="play-pause-btn w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition"
                                        data-audio-id="audio-{{ $audio->id }}"
                                        aria-label="Reproducir / Pausar"
                                    >
                                        <i class="fas fa-play play-icon"></i>
                                        <i class="fas fa-pause pause-icon hidden"></i>
                                    </button>

                                    <audio id="audio-{{ $audio->id }}" preload="none" class="flex-grow rounded bg-gray-100" style="height: 32px;">
                                        <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                        Tu navegador no soporta la reproducción de audio.
                                    </audio>

                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        value="0"
                                        class="progress-bar flex-grow h-1 rounded-lg cursor-pointer bg-indigo-300"
                                        data-audio-id="audio-{{ $audio->id }}"
                                        aria-label="Barra de progreso"
                                    />

                                    {{-- Volumen control --}}
                                    <button
                                        type="button"
                                        class="volume-toggle-btn w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition"
                                        data-audio-id="audio-{{ $audio->id }}"
                                        aria-label="Mute / Unmute"
                                    >
                                        <i class="fas fa-volume-up volume-on-icon"></i>
                                        <i class="fas fa-volume-mute volume-off-icon hidden"></i>
                                    </button>
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        value="100"
                                        class="volume-slider w-24 cursor-pointer"
                                        data-audio-id="audio-{{ $audio->id }}"
                                        aria-label="Control de volumen"
                                    />

                                    {{-- Botón descarga con mismo estilo --}}
                                    <a 
                                        href="{{ asset('storage/' . $audio->archivo) }}" 
                                        download="{{ $audio->titulo }}{{ \Illuminate\Support\Str::startsWith($audio->archivo, '.') ? $audio->archivo : '.' . pathinfo($audio->archivo, PATHINFO_EXTENSION) }}"
                                        class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition"
                                        title="Descargar {{ $audio->titulo }}"
                                    >
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            @elseif (Str::startsWith($audio->filetype, 'video'))
                                <video controls class="w-full rounded-md" preload="metadata" style="max-height: 180px;">
                                    <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                    Tu navegador no soporta la reproducción de video.
                                </video>
                            @endif

                            {{-- Comentarios ocultos con toggle --}}
                            <section>
                                <button 
                                    type="button" 
                                    class="text-indigo-600 text-sm font-medium mb-2 focus:outline-none"
                                    onclick="const c=document.getElementById('comments-{{ $audio->id }}'); c.classList.toggle('hidden'); this.textContent = this.textContent.includes('Mostrar') ? 'Ocultar comentarios' : 'Mostrar comentarios';"
                                >
                                    Mostrar comentarios ({{ $audio->comments->count() }})
                                </button>

                                <div id="comments-{{ $audio->id }}" class="hidden">
                                    @auth
                                    <form action="{{ route('comments.store', $audio) }}" method="POST" class="mb-4">
                                        @csrf
                                        <textarea
                                            name="content"
                                            rows="1"
                                            required
                                            placeholder="Añade un comentario..."
                                            class="w-full rounded border border-gray-300 p-3 resize-y focus:ring-indigo-500 focus:border-indigo-500"
                                        ></textarea>
                                        <button type="submit" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">
                                            Comentar
                                        </button>
                                    </form>
                                    @else
                                        <p class="text-sm text-gray-500 italic">Debes <a href="{{ route('login') }}" class="text-indigo-600 underline">iniciar sesión</a> para comentar.</p>
                                    @endauth

                                    @forelse ($audio->comments as $comment)
                                        <div class="border border-gray-200 rounded p-3 mb-2 bg-gray-50">
                                            <p class="text-sm text-gray-800">{{ $comment->content }}</p>
                                            <p class="text-xs text-gray-500 mt-1">Por {{ $comment->user->name }} - {{ $comment->created_at->diffForHumans() }}</p>
                                        </div>
                                    @empty
                                        <p class="text-gray-500 italic text-sm">No hay comentarios aún.</p>
                                    @endforelse
                                </div>
                            </section>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $audios->withQueryString()->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 text-sm">No hay audios disponibles para los filtros seleccionados.</p>
            @endif

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const players = document.querySelectorAll('audio');

            players.forEach(audio => {
                const id = audio.id;
                const playPauseBtn = document.querySelector(`button.play-pause-btn[data-audio-id="${id}"]`);
                const progressBar = document.querySelector(`input.progress-bar[data-audio-id="${id}"]`);
                const volumeToggleBtn = document.querySelector(`button.volume-toggle-btn[data-audio-id="${id}"]`);
                const volumeSlider = document.querySelector(`input.volume-slider[data-audio-id="${id}"]`);

                // Actualizar barra de progreso
                audio.addEventListener('timeupdate', () => {
                    if (audio.duration) {
                        const progressPercent = (audio.currentTime / audio.duration) * 100;
                        progressBar.value = progressPercent;
                    }
                });

                // Play / pause
                playPauseBtn.addEventListener('click', () => {
                    if (audio.paused) {
                        players.forEach(p => { if (p !== audio) p.pause(); });
                        audio.play();
                        playPauseBtn.querySelector('.fa-play').classList.add('hidden');
                        playPauseBtn.querySelector('.fa-pause').classList.remove('hidden');
                    } else {
                        audio.pause();
                        playPauseBtn.querySelector('.fa-play').classList.remove('hidden');
                        playPauseBtn.querySelector('.fa-pause').classList.add('hidden');
                    }
                });

                // Cambiar audio con barra progreso
                progressBar.addEventListener('input', () => {
                    if (audio.duration) {
                        audio.currentTime = (progressBar.value / 100) * audio.duration;
                    }
                });

                // Volumen slider
                volumeSlider.addEventListener('input', () => {
                    audio.volume = volumeSlider.value / 100;
                    if (audio.volume === 0) {
                        volumeToggleBtn.querySelector('.volume-on-icon').classList.add('hidden');
                        volumeToggleBtn.querySelector('.volume-off-icon').classList.remove('hidden');
                    } else {
                        volumeToggleBtn.querySelector('.volume-on-icon').classList.remove('hidden');
                        volumeToggleBtn.querySelector('.volume-off-icon').classList.add('hidden');
                    }
                });

                // Mute / unmute toggle
                volumeToggleBtn.addEventListener('click', () => {
                    if (audio.volume > 0) {
                        audio.dataset.lastVolume = audio.volume;
                        audio.volume = 0;
                        volumeSlider.value = 0;
                        volumeToggleBtn.querySelector('.volume-on-icon').classList.add('hidden');
                        volumeToggleBtn.querySelector('.volume-off-icon').classList.remove('hidden');
                    } else {
                        const lastVol = audio.dataset.lastVolume ?? 1;
                        audio.volume = lastVol;
                        volumeSlider.value = lastVol * 100;
                        volumeToggleBtn.querySelector('.volume-on-icon').classList.remove('hidden');
                        volumeToggleBtn.querySelector('.volume-off-icon').classList.add('hidden');
                    }
                });

                // Cuando termina audio
                audio.addEventListener('ended', () => {
                    playPauseBtn.querySelector('.fa-play').classList.remove('hidden');
                    playPauseBtn.querySelector('.fa-pause').classList.add('hidden');
                    progressBar.value = 0;
                });
            });
        });
    </script>
</x-app-layout>
