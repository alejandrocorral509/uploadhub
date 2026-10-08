import Swal from 'sweetalert2';
import { csrfFetch } from './http';

// Aviso breve en la esquina
export function toast(title, icon = 'success') {
    Swal.fire({
        title,
        icon,
        toast: true,
        position: 'top-end',
        timer: 2600,
        timerProgressBar: true,
        showConfirmButton: false,
    });
}

const confirmDefaults = {
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, continuar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#4f46e5',
    cancelButtonColor: '#6b7280',
    reverseButtons: true,
};

export function registerComponents(Alpine) {
    window.toast = toast;

    // Formulario que pide confirmación antes de enviarse (borrar, cerrar sesión...)
    Alpine.data('confirmForm', (options = {}) => ({
        confirmed: false,
        async submit(event) {
            if (this.confirmed) return;
            event.preventDefault();
            const result = await Swal.fire({
                ...confirmDefaults,
                ...(options.danger ? { confirmButtonColor: '#dc2626' } : {}),
                title: options.title ?? '¿Seguro?',
                text: options.text ?? '',
                confirmButtonText: options.confirm ?? confirmDefaults.confirmButtonText,
            });
            if (result.isConfirmed) {
                this.confirmed = true;
                this.$el.requestSubmit();
            }
        },
    }));

    // Botón "Me gusta" sin recargar la página
    Alpine.data('likeButton', (url, liked, count) => ({
        liked,
        count,
        busy: false,
        async toggle() {
            if (this.busy) return;
            this.busy = true;
            // Respuesta inmediata; si falla, se deshace
            this.liked = !this.liked;
            this.count += this.liked ? 1 : -1;
            try {
                const response = await csrfFetch(url, { method: 'POST' });
                if (!response.ok) throw new Error();
                const data = await response.json();
                this.liked = data.liked;
                this.count = data.likes_count;
            } catch {
                this.liked = !this.liked;
                this.count += this.liked ? 1 : -1;
                toast('No se ha podido guardar. Inténtalo de nuevo.', 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    // Botón "Suscribirse" a un programa
    Alpine.data('subscribeButton', (url, subscribed, count) => ({
        subscribed,
        count,
        busy: false,
        async toggle() {
            if (this.busy) return;
            this.busy = true;
            try {
                const response = await csrfFetch(url, { method: 'POST' });
                if (!response.ok) throw new Error();
                const data = await response.json();
                this.subscribed = data.subscribed;
                this.count = data.subscribers_count;
                toast(this.subscribed ? 'Te has suscrito. Verás sus episodios en tu biblioteca.' : 'Suscripción cancelada', this.subscribed ? 'success' : 'info');
            } catch {
                toast('No se ha podido guardar. Inténtalo de nuevo.', 'error');
            } finally {
                this.busy = false;
            }
        },
    }));

    // Compartir: menú nativo del móvil si existe; si no, copiar el enlace
    Alpine.data('shareButton', (url, title) => ({
        menu: false,
        async share() {
            if (navigator.share) {
                try {
                    await navigator.share({ title, url });
                    return;
                } catch (e) {
                    if (e.name === 'AbortError') return;
                }
            }
            this.copy();
        },
        async copy(text = url, message = 'Enlace copiado') {
            try {
                await navigator.clipboard.writeText(text);
                toast(message);
            } catch {
                window.prompt('Copia el enlace:', text);
            }
        },
        whatsapp() {
            window.open(`https://api.whatsapp.com/send?text=${encodeURIComponent(`${title} ${url}`)}`, '_blank', 'noopener');
        },
        email() {
            window.location.href = `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent(url)}`;
        },
    }));

    // Mensajes flash de Laravel, mostrados como aviso al cargar cada página
    Alpine.data('flash', (message, icon = 'success') => ({
        init() {
            if (message) toast(message, icon);
        },
    }));

    // Búsqueda dentro de la transcripción
    Alpine.data('transcript', () => ({
        query: '',
        open: false,
        matches(text) {
            const q = this.query.trim().toLowerCase();
            return q === '' || text.toLowerCase().includes(q);
        },
        // Devuelve HTML seguro: se escapa todo y luego se marcan las coincidencias
        highlight(text) {
            const escape = (s) => s.replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c]);
            const safe = escape(text);
            const q = this.query.trim();
            if (!q) return safe;
            const pattern = new RegExp(escape(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
            return safe.replace(pattern, (m) => `<mark class="rounded bg-amber-200 px-0.5">${m}</mark>`);
        },
    }));

    // Forma de onda del episodio: muestra el progreso y permite saltar a un punto con un clic
    Alpine.data('waveform', (episode) => ({
        get progress() {
            const player = Alpine.store('player');
            if (!player.isCurrent(episode.id)) return 0;
            const duration = player.duration || episode.duration || 0;
            return duration ? Math.min(1, player.position / duration) : 0;
        },
        seekTo(event) {
            const rect = this.$el.getBoundingClientRect();
            const ratio = Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width));
            const duration = episode.duration || Alpine.store('player').duration;
            if (!duration) {
                Alpine.store('player').play(episode);
                return;
            }
            Alpine.store('player').playAt(episode, Math.floor(ratio * duration));
        },
    }));

    // Vídeos: se reproducen en la propia página, pero también cuentan como escuchas
    Alpine.data('videoTracker', (playsUrl) => ({
        updateUrl: null,
        last: 0,
        async onPlay() {
            if (this.updateUrl) return;
            const response = await csrfFetch(playsUrl, { method: 'POST' }).catch(() => null);
            if (response?.ok) this.updateUrl = (await response.json()).update_url;
        },
        onTime(event) {
            const video = event.target;
            if (!this.updateUrl || Math.abs(video.currentTime - this.last) < 15) return;
            this.last = video.currentTime;
            csrfFetch(this.updateUrl, {
                method: 'PATCH',
                body: JSON.stringify({ position: Math.floor(video.currentTime), duration: Math.floor(video.duration || 0) }),
            }).catch(() => {});
        },
    }));

    // Tooltip de las gráficas: guarda el punto activo; el texto se pinta con x-text (nunca HTML)
    Alpine.data('chart', (points) => ({
        points,
        active: null,
        get point() {
            return this.active === null ? null : this.points[this.active];
        },
        // Línea: la cruz busca el punto más cercano a la X del puntero
        track(event) {
            const rect = this.$refs.plot.getBoundingClientRect();
            const ratio = (event.clientX - rect.left) / rect.width;
            this.active = Math.max(0, Math.min(this.points.length - 1, Math.round(ratio * (this.points.length - 1))));
        },
    }));

    // Vista previa de la portada al elegir una imagen
    Alpine.data('imagePreview', (initial = null) => ({
        preview: initial,
        remove: false,
        pick(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.remove = false;
            this.preview = URL.createObjectURL(file);
        },
        clear() {
            this.preview = null;
            this.remove = true;
            this.$refs.input.value = '';
        },
    }));
}
