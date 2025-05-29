<x-app-layout>
    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 bg-white rounded-lg shadow-md p-6">

            {{-- Buscador y filtro por categoría --}}
            <form method="GET" action="{{ route('audios.public') }}" class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
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
                        <article class="relative border border-gray-200 rounded-md p-4 hover:shadow-md transition-shadow duration-150 flex gap-4">
                            <!-- Botón compartir -->
                            <button
                                class="share-btn absolute top-2 right-2 text-gray-500 hover:text-indigo-600 focus:outline-none"
                                data-audio-id="{{ $audio->id }}"
                                data-type="{{ $audio->youtube_url ? 'youtube' : 'audio' }}"
                                data-url="{{ $audio->youtube_url ? $audio->youtube_url : asset('storage/' . $audio->filepath) }}"
                                title="Compartir {{ $audio->title }}"
                            >
                                <i class="fas fa-share-alt fa-lg"></i>
                            </button>
                            <div class="share-menu absolute top-8 right-2 bg-white border border-gray-300 rounded shadow-md p-2 hidden z-50 text-sm" data-audio-id="{{ $audio->id }}">
                                <button class="share-whatsapp block w-full text-left px-2 py-1 hover:bg-gray-100">WhatsApp</button>
                                <button class="share-gmail block w-full text-left px-2 py-1 hover:bg-gray-100">Gmail</button>
                                <button class="share-copy block w-full text-left px-2 py-1 hover:bg-gray-100">Copiar enlace</button>
                            </div>

                            {{-- Imagen de portada --}}
                            @if ($audio->cover)
                                <img src="{{ asset('storage/' . $audio->cover) }}" alt="Portada {{ $audio->title }}" class="w-24 h-24 object-cover rounded-md shadow-sm flex-shrink-0">
                            @endif
                            <div class="flex-grow">
                                <header class="mb-1">
                                    <h2 class="font-semibold text-lg text-gray-800 truncate">{{ $audio->title }}</h2>
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

                                {{-- Botón Me gusta --}}
                                <div class="flex items-center space-x-3 mb-3">
                                    @auth
                                        <button
                                            type="button"
                                            class="like-btn flex items-center space-x-1 text-indigo-600 hover:text-indigo-800 focus:outline-none"
                                            data-audio-id="{{ $audio->id }}"
                                            aria-pressed="{{ $audio->likes->contains(auth()->user()->id) ? 'true' : 'false' }}"
                                            title="Me gusta"
                                        >
                                            <svg 
                                                xmlns="http://www.w3.org/2000/svg" 
                                                class="h-5 w-5" 
                                                fill="{{ $audio->likes->contains(auth()->user()->id) ? 'currentColor' : 'none' }}" 
                                                viewBox="0 0 24 24" 
                                                stroke="currentColor" 
                                                stroke-width="2"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 016.364 0L12 7.636l1.318-1.318a4.5 4.5 0 116.364 6.364L12 21.364l-7.682-7.682a4.5 4.5 0 010-6.364z" />
                                            </svg>
                                            <span class="text-sm font-semibold">{{ $audio->likes->count() }}</span>
                                        </button>
                                    @else
                                        <span class="text-gray-500 text-sm">Likes: {{ $audio->likes->count() }}</span>
                                    @endauth
                                </div>

                                {{-- Reproductor o video YouTube embebido --}}
                                @if ($audio->youtube_url)
                                    @php
                                        preg_match('/(youtu\.be\/|v=)([^&]+)/', $audio->youtube_url, $matches);
                                        $videoId = $matches[2] ?? null;
                                    @endphp

                                    @if ($videoId)
                                        <div class="mb-3">
                                            <iframe
                                                width="100%"
                                                height="315"
                                                src="https://www.youtube.com/embed/{{ $videoId }}"
                                                title="YouTube video player"
                                                frameborder="0"
                                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                allowfullscreen
                                            ></iframe>
                                        </div>
                                    @endif
                                @elseif (Str::startsWith($audio->filetype, 'audio'))
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
                                            <source src="{{ asset('storage/' . $audio->filepath) }}" type="{{ $audio->filetype }}">
                                            Tu navegador no soporta la reproducción de audio.
                                        </audio>

                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.1"
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
                                        <a href="{{ route('audios.download', $audio) }}" 
                                            class="w-10 h-10 flex items-center justify-center rounded-full bg-indigo-600 text-white hover:bg-indigo-700 transition"
                                            title="Descargar {{ $audio->title }}">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                @elseif (Str::startsWith($audio->filetype, 'video'))
                                    <video controls class="w-full rounded-md" preload="metadata" style="max-height: 180px;">
                                        <source src="{{ asset('storage/' . $audio->filepath) }}" type="{{ $audio->filetype }}">
                                        Tu navegador no soporta la reproducción de video.
                                    </video>
                                @endif

                                {{-- Comentarios ocultos con toggle --}}
                                <section>
                                    <button 
                                        type="button" 
                                        class="comments-toggle-btn text-indigo-600 text-sm font-medium mb-2 focus:outline-none"
                                        data-audio-id="{{ $audio->id }}"
                                        aria-expanded="false"
                                    >
                                        <span class="toggle-text">Mostrar comentarios</span> 
                                        (<span class="comments-count">{{ $audio->comments->count() }}</span>)
                                    </button>

                                    <div id="comments-{{ $audio->id }}" class="hidden comments-container">
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
                            </div>
                        </article>
                    @endforeach
                </div>

            @else
                <p class="text-center text-gray-500 text-sm">No hay audios disponibles para los filtros seleccionados.</p>
            @endif

        </div>
        <div class="mt-6">
            {{ $audios->withQueryString()->links() }}
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

                    let isSeeking = false;

                    progressBar.addEventListener('mousedown', () => {
                        isSeeking = true;
                        if (!audio.readyState) {
                            audio.load();
                        }
                    });

                    progressBar.addEventListener('mouseup', () => {
                        if (audio.duration) {
                            audio.currentTime = (progressBar.value / 100) * audio.duration;
                        }
                        isSeeking = false;
                    });

                    audio.addEventListener('loadedmetadata', () => {
                        progressBar.max = 100; // redundante pero seguro
                        progressBar.value = 0;
                    });

                    // Actualizar barra sólo si no estás arrastrando
                    audio.addEventListener('timeupdate', () => {
                        if (!isSeeking && audio.duration) {
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

            // Toggle like button
            const likeButtons = document.querySelectorAll('.like-btn');

            likeButtons.forEach(button => {
                button.addEventListener('click', async () => {
                    const audioId = button.dataset.audioId;

                    try {
                        const response = await fetch(`/audios/${audioId}/toggle-like`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'Content-Type': 'application/json'
                            },
                        });

                        if (!response.ok) throw new Error('Error en la respuesta');

                        const data = await response.json();

                        // Actualizar contador
                        button.querySelector('span').textContent = data.likes_count;

                        // Actualizar icono según liked
                        const svg = button.querySelector('svg');
                        if (data.liked) {
                            svg.setAttribute('fill', 'currentColor');
                            button.setAttribute('aria-pressed', 'true');
                        } else {
                            svg.setAttribute('fill', 'none');
                            button.setAttribute('aria-pressed', 'false');
                        }
                    } catch (error) {
                        console.error('Error toggling like:', error);
                    }
                });
            });

            // Toggle comentarios con ocultación del número
            document.querySelectorAll('.comments-toggle-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const audioId = button.dataset.audioId;
                    const commentsDiv = document.getElementById(`comments-${audioId}`);

                    const isHidden = commentsDiv.classList.contains('hidden');
                    const toggleTextSpan = button.querySelector('.toggle-text');

                    if (isHidden) {
                        commentsDiv.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                        toggleTextSpan.textContent = 'Ocultar comentarios';
                    } else {
                        commentsDiv.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                        toggleTextSpan.textContent = 'Mostrar comentarios';
                    }
                });
            });

            // Funcionalidad para abrir/cerrar menú compartir
            const shareButtons = document.querySelectorAll('.share-btn');

            shareButtons.forEach(button => {
                button.addEventListener('click', (e) => {
                    e.stopPropagation();

                    const audioId = button.dataset.audioId;
                    const menu = document.querySelector(`.share-menu[data-audio-id="${audioId}"]`);

                    // Cerrar otros menús abiertos
                    document.querySelectorAll('.share-menu').forEach(m => {
                        if(m !== menu) m.classList.add('hidden');
                    });

                    // Toggle menú actual
                    menu.classList.toggle('hidden');
                });
            });

            // Cerrar menú al clicar fuera
            document.addEventListener('click', () => {
                document.querySelectorAll('.share-menu').forEach(menu => menu.classList.add('hidden'));
            });

            // Funcionalidad para compartir
            document.querySelectorAll('.share-menu').forEach(menu => {
                const audioId = menu.dataset.audioId;
                const shareBtn = document.querySelector(`.share-btn[data-audio-id="${audioId}"]`);

                const url = shareBtn.dataset.url;
                const type = shareBtn.dataset.type;
                const title = shareBtn.title.replace('Compartir ', '');

                // WhatsApp
                menu.querySelector('.share-whatsapp').addEventListener('click', () => {
                    const text = type === 'youtube' 
                        ? `Mira este vídeo en YouTube: ${url}`
                        : `Escucha este audio: ${url}`;
                    const whatsappUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(text)}`;
                    window.open(whatsappUrl, '_blank');
                    mostrarMensajeCompartir(type);
                    menu.classList.add('hidden');
                });

                // Gmail
                menu.querySelector('.share-gmail').addEventListener('click', () => {
                    const subject = encodeURIComponent(`Compartir ${type === 'youtube' ? 'vídeo' : 'audio'}: ${title}`);
                    const body = encodeURIComponent(type === 'youtube' 
                        ? `Te comparto este vídeo de YouTube:\n${url}` 
                        : `Te comparto este audio:\n${url}`);
                    const mailtoUrl = `mailto:?subject=${subject}&body=${body}`;
                    window.location.href = mailtoUrl;
                    menu.classList.add('hidden');
                    // No mensaje porque es abrir mailto
                });

                // Copiar enlace
                menu.querySelector('.share-copy').addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(url);
                        mostrarMensajeCompartir(type, true);
                    } catch (e) {
                        alert('No se pudo copiar el enlace.');
                    }
                    menu.classList.add('hidden');
                });
            });

            function mostrarMensajeCompartir(type, copiado = false) {
                if (copiado) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Enlace copiado al portapapeles',
                        toast: true,
                        position: 'top-end',
                        timer: 2500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: type === 'youtube' ? 'Compartido vídeo de YouTube' : 'Compartido audio',
                        toast: true,
                        position: 'top-end',
                        timer: 2500,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                }
            }
        });
    </script>

    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: '{{ session('success') }}',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    timerProgressBar: true,
                    showConfirmButton: false
                });
            });
        </script>
    @endif

</x-app-layout>
