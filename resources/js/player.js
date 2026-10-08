// Reproductor global. Vive en un elemento con data-turbo-permanent, así que sigue sonando
// al navegar entre páginas. El estado está en un store de Alpine para que cualquier botón
// de la página pueda reproducir, poner en cola o saber qué está sonando.

import { csrfFetch } from './http';

const STATE_KEY = 'uploadhub.player';
const POSITIONS_KEY = 'uploadhub.positions';
const SPEEDS = [1, 1.25, 1.5, 1.75, 2, 0.75];
const HEARTBEAT_SECONDS = 15;

const storage = {
    get(key, fallback) {
        try {
            return JSON.parse(localStorage.getItem(key)) ?? fallback;
        } catch {
            return fallback;
        }
    },
    set(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
        } catch {
            // Modo privado o almacenamiento lleno: el reproductor sigue funcionando sin recordar
        }
    },
};

export function formatTime(seconds) {
    if (!Number.isFinite(seconds) || seconds < 0) return '0:00';
    const s = Math.floor(seconds);
    const h = Math.floor(s / 3600);
    const m = Math.floor((s % 3600) / 60);
    const sec = String(s % 60).padStart(2, '0');
    return h > 0 ? `${h}:${String(m).padStart(2, '0')}:${sec}` : `${m}:${sec}`;
}

export function registerPlayer(Alpine) {
    Alpine.store('player', {
        current: null,
        queue: [],
        playing: false,
        loading: false,
        position: 0,
        duration: 0,
        speed: 1,
        volume: 1,
        muted: false,
        seeking: false,
        showQueue: false,
        error: null,
        updateUrl: null,
        lastBeat: 0,

        owner: '',

        restore() {
            const saved = storage.get(STATE_KEY, {});
            this.owner = saved.owner ?? '';
            if (this.checkUser()) return;
            this.queue = Array.isArray(saved.queue) ? saved.queue : [];
            this.speed = SPEEDS.includes(saved.speed) ? saved.speed : 1;
            this.volume = typeof saved.volume === 'number' ? saved.volume : 1;
            if (saved.current) {
                // Se recupera lo último que sonaba, en pausa, en el punto donde se quedó
                this.load(saved.current, false);
            }
        },

        // Si cambia la cuenta (cerrar sesión o entrar con otra), lo que escuchaba la anterior no se queda.
        // De invitado a usuario sí se conserva. Devuelve true si se ha vaciado.
        checkUser() {
            const user = document.body.dataset.user ?? '';
            const changed = this.owner !== '' && this.owner !== user;
            this.owner = user;
            if (changed) {
                this.close();
                this.queue = [];
                storage.set(POSITIONS_KEY, {});
                this.persist();
            }
            return changed;
        },

        get audio() {
            return document.getElementById('player-audio');
        },

        isCurrent(id) {
            return this.current?.id === id;
        },

        isPlaying(id) {
            return this.isCurrent(id) && this.playing;
        },

        // Reproduce un episodio o, si ya es el actual, pausa/reanuda
        play(episode) {
            if (this.isCurrent(episode.id)) {
                this.toggle();
                return;
            }
            this.load(episode, true);
        },

        playAt(episode, seconds) {
            if (!this.isCurrent(episode.id)) {
                this.load({ ...episode, resumeAt: seconds }, true);
                return;
            }
            this.seek(seconds);
            this.audio?.play();
        },

        load(episode, autoplay = true) {
            const audio = this.audio;
            if (!audio) return;

            this.sendBeat(true);
            this.current = episode;
            this.error = null;
            this.position = 0;
            this.duration = episode.duration || 0;
            this.updateUrl = null;
            this.loading = autoplay;

            const start = episode.resumeAt ?? storage.get(POSITIONS_KEY, {})[episode.id] ?? 0;

            audio.src = episode.src;
            audio.playbackRate = this.speed;
            audio.volume = this.volume;
            audio.muted = this.muted;

            const applyStart = () => {
                if (start > 0 && start < (audio.duration || Infinity) - 5) {
                    audio.currentTime = start;
                    this.position = start;
                }
            };
            audio.addEventListener('loadedmetadata', applyStart, { once: true });
            audio.load();

            if (autoplay) {
                audio.play().catch(() => {
                    this.loading = false;
                });
                this.registerPlay();
            } else {
                this.position = start;
            }

            this.updateMediaSession();
            this.persist();
        },

        toggle() {
            const audio = this.audio;
            if (!audio || !this.current) return;
            if (audio.paused) {
                if (!this.updateUrl) this.registerPlay();
                audio.play().catch(() => {});
            } else {
                audio.pause();
            }
        },

        seek(seconds) {
            const audio = this.audio;
            if (!audio || !this.current) return;
            const max = audio.duration || this.duration || 0;
            const target = Math.max(0, Math.min(Number(seconds), max || Number(seconds)));
            audio.currentTime = target;
            this.position = target;
        },

        skip(delta) {
            this.seek((this.audio?.currentTime || 0) + delta);
        },

        cycleSpeed() {
            const index = SPEEDS.indexOf(this.speed);
            this.speed = SPEEDS[(index + 1) % SPEEDS.length];
            if (this.audio) this.audio.playbackRate = this.speed;
            this.persist();
        },

        setVolume(value) {
            this.volume = Math.max(0, Math.min(1, Number(value)));
            this.muted = this.volume === 0;
            if (this.audio) {
                this.audio.volume = this.volume;
                this.audio.muted = this.muted;
            }
            this.persist();
        },

        toggleMute() {
            this.muted = !this.muted;
            if (this.audio) this.audio.muted = this.muted;
        },

        enqueue(episode) {
            if (this.isCurrent(episode.id) || this.queue.some((e) => e.id === episode.id)) {
                window.toast?.('Ya está en la cola', 'info');
                return;
            }
            this.queue.push(episode);
            this.persist();
            window.toast?.('Añadido a la cola');
            if (!this.current) this.next(false);
        },

        inQueue(id) {
            return this.queue.some((e) => e.id === id);
        },

        removeFromQueue(index) {
            this.queue.splice(index, 1);
            this.persist();
        },

        clearQueue() {
            this.queue = [];
            this.persist();
        },

        next(autoplay = true) {
            const episode = this.queue.shift();
            this.persist();
            if (episode) this.load(episode, autoplay);
        },

        close() {
            this.sendBeat(true);
            this.audio?.pause();
            if (this.audio) this.audio.removeAttribute('src');
            this.current = null;
            this.playing = false;
            this.persist();
        },

        // --- Eventos del elemento <audio> -------------------------------------

        onTimeUpdate() {
            const audio = this.audio;
            if (!audio || this.seeking) return;
            this.position = audio.currentTime;
            if (audio.duration && Number.isFinite(audio.duration)) this.duration = audio.duration;

            if (Math.abs(this.position - this.lastBeat) >= HEARTBEAT_SECONDS) {
                this.sendBeat(false);
                this.savePosition();
            }
        },

        onEnded() {
            this.playing = false;
            this.sendBeat(true);
            const positions = storage.get(POSITIONS_KEY, {});
            delete positions[this.current?.id];
            storage.set(POSITIONS_KEY, positions);
            if (this.queue.length) this.next(true);
        },

        onError() {
            if (!this.current) return;
            this.loading = false;
            this.playing = false;
            this.error = 'No se ha podido reproducir este episodio.';
        },

        // --- Escuchas y progreso ------------------------------------------------

        async registerPlay() {
            if (!this.current?.playsUrl) return;
            const episodeId = this.current.id;
            try {
                const response = await csrfFetch(this.current.playsUrl, { method: 'POST' });
                if (response.ok && this.current?.id === episodeId) {
                    const data = await response.json();
                    this.updateUrl = data.update_url;
                    this.lastBeat = this.position;
                }
            } catch {
                // Sin conexión: se reproduce igual, solo que no cuenta en las estadísticas
            }
        },

        sendBeat(final) {
            if (!this.updateUrl || !this.current) return;
            this.lastBeat = this.position;
            csrfFetch(this.updateUrl, {
                method: 'PATCH',
                keepalive: final,
                body: JSON.stringify({
                    position: Math.floor(this.position),
                    duration: Number.isFinite(this.duration) ? Math.floor(this.duration) : null,
                }),
            }).catch(() => {});
        },

        savePosition() {
            if (!this.current) return;
            const positions = storage.get(POSITIONS_KEY, {});
            positions[this.current.id] = Math.floor(this.position);
            // Solo se recuerdan los 100 últimos
            const ids = Object.keys(positions);
            if (ids.length > 100) delete positions[ids[0]];
            storage.set(POSITIONS_KEY, positions);
            this.persist();
        },

        persist() {
            storage.set(STATE_KEY, {
                owner: this.owner,
                current: this.current ? { ...this.current, resumeAt: Math.floor(this.position) } : null,
                queue: this.queue,
                speed: this.speed,
                volume: this.volume,
            });
        },

        // Controles de la pantalla de bloqueo y de los auriculares
        updateMediaSession() {
            if (!('mediaSession' in navigator) || !this.current) return;
            navigator.mediaSession.metadata = new MediaMetadata({
                title: this.current.title,
                artist: this.current.author || 'UploadHub',
                artwork: this.current.cover ? [{ src: this.current.cover, sizes: '512x512' }] : [],
            });
            const handlers = {
                play: () => this.toggle(),
                pause: () => this.toggle(),
                seekbackward: () => this.skip(-15),
                seekforward: () => this.skip(30),
                nexttrack: () => this.next(true),
                seekto: (details) => this.seek(details.seekTime),
            };
            for (const [action, handler] of Object.entries(handlers)) {
                try {
                    navigator.mediaSession.setActionHandler(action, handler);
                } catch {
                    // Acción no admitida por este navegador
                }
            }
        },

        formatTime,
    });

    // El adaptador de Alpine para Turbo vuelve a poner x-cloak antes de guardar la página en caché.
    // El reproductor es permanente (no se reinicializa), así que hay que quitárselo o quedaría oculto.
    // Se registra en alpine:init para que se ejecute después del listener del adaptador.
    document.addEventListener(
        'alpine:init',
        () => {
            document.addEventListener('turbo:before-cache', () => {
                document.querySelectorAll('#global-player [x-cloak], #global-player [data-alpine-was-cloaked]').forEach((el) => {
                    el.removeAttribute('x-cloak');
                    el.removeAttribute('data-alpine-was-cloaked');
                });
            });
        },
        { once: true },
    );

    // Componente de la barra inferior: conecta los eventos del <audio> con el store
    Alpine.data('playerBar', () => ({
        init() {
            const audio = this.$refs.audio;
            const store = Alpine.store('player');
            audio.addEventListener('timeupdate', () => store.onTimeUpdate());
            audio.addEventListener('play', () => {
                store.playing = true;
            });
            audio.addEventListener('playing', () => {
                store.loading = false;
            });
            audio.addEventListener('waiting', () => {
                store.loading = true;
            });
            // "waiting" activa la rueda de carga; cualquiera de estos eventos significa que ya no se espera
            for (const event of ['canplay', 'seeked', 'loadeddata']) {
                audio.addEventListener(event, () => {
                    if (audio.paused || audio.readyState >= 3) store.loading = false;
                });
            }
            audio.addEventListener('pause', () => {
                store.loading = false;
                store.playing = false;
                store.savePosition();
                store.sendBeat(false);
            });
            audio.addEventListener('ended', () => store.onEnded());
            audio.addEventListener('error', () => store.onError());
            audio.addEventListener('durationchange', () => {
                if (Number.isFinite(audio.duration)) store.duration = audio.duration;
            });

            // Al cerrar la pestaña se guarda la posición
            window.addEventListener('pagehide', () => {
                store.savePosition();
                store.sendBeat(true);
            });

            store.restore();
            document.addEventListener('turbo:load', () => store.checkUser());
        },

        // Barra de progreso: "input" mientras se arrastra (ratón, dedo o teclado), "change" al soltar
        onScrub(event) {
            const store = Alpine.store('player');
            store.seeking = true;
            store.position = Number(event.target.value);
        },
        onScrubEnd(event) {
            const store = Alpine.store('player');
            store.seeking = false;
            store.seek(event.target.value);
        },
    }));
}
