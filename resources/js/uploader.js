import { csrfFetch } from './http';

// Formulario de episodio (crear y editar).
// El archivo se sube por trozos en cuanto se elige, mientras se rellenan el resto de campos.
export function episodeForm(config) {
    return {
        mode: config.mode ?? 'file',
        categories: config.categories ?? [],
        maxCategories: 3,

        fileName: null,
        fileSize: 0,
        progress: 0,
        uploading: false,
        uploadDone: false,
        uploadError: null,
        token: null,
        cancelled: false,

        errors: {},
        submitting: false,

        toggleCategory(id) {
            if (this.categories.includes(id)) {
                this.categories = this.categories.filter((c) => c !== id);
            } else if (this.categories.length < this.maxCategories) {
                this.categories.push(id);
            } else {
                window.toast?.(`Puedes elegir como máximo ${this.maxCategories} categorías`, 'info');
            }
            delete this.errors.categories;
        },

        error(field) {
            return this.errors[field]?.[0] ?? null;
        },

        formatSize(bytes) {
            return bytes > 1024 * 1024 ? `${(bytes / 1024 / 1024).toFixed(1)} MB` : `${Math.ceil(bytes / 1024)} KB`;
        },

        pickFile(event) {
            const file = event.target.files[0];
            event.target.value = '';
            if (!file) return;

            const extension = file.name.split('.').pop().toLowerCase();
            if (!config.extensions.includes(extension)) {
                this.uploadError = `Formato no admitido. Usa ${config.extensions.join(', ').toUpperCase()}.`;
                return;
            }
            if (file.size > config.maxMb * 1024 * 1024) {
                this.uploadError = `El archivo pesa ${this.formatSize(file.size)} y el máximo es ${config.maxMb} MB.`;
                return;
            }

            this.upload(file);
        },

        async upload(file) {
            await this.cancelUpload();
            this.fileName = file.name;
            this.fileSize = file.size;
            this.progress = 0;
            this.uploading = true;
            this.uploadDone = false;
            this.uploadError = null;
            this.cancelled = false;
            delete this.errors.audio;

            try {
                const start = await csrfFetch(config.startUrl, {
                    method: 'POST',
                    body: JSON.stringify({ filename: file.name, size: file.size }),
                });
                const startData = await start.json();
                if (!start.ok) throw new Error(startData.errors?.file?.[0] ?? 'No se pudo empezar la subida.');

                this.token = startData.token;
                const chunkSize = startData.chunk_size;
                let offset = 0;

                while (offset < file.size) {
                    if (this.cancelled) return;
                    const blob = file.slice(offset, offset + chunkSize);
                    const result = await this.sendChunk(blob, offset);
                    offset = result.received;
                    this.progress = Math.round((offset / file.size) * 100);
                }

                this.uploading = false;
                this.uploadDone = true;
            } catch (e) {
                if (this.cancelled) return;
                this.uploading = false;
                this.token = null;
                this.uploadError = e.message || 'Se ha cortado la subida. Vuelve a elegir el archivo.';
            }
        },

        // Cada trozo se reintenta hasta 3 veces (conexiones móviles inestables)
        async sendChunk(blob, offset) {
            let lastError;
            for (let attempt = 0; attempt < 3; attempt++) {
                try {
                    const body = new FormData();
                    body.append('chunk', blob, 'chunk');
                    body.append('offset', offset);
                    const response = await csrfFetch(config.chunkUrl.replace('__TOKEN__', this.token), { method: 'POST', body });
                    if (response.ok) return await response.json();
                    if (response.status === 404 || response.status === 422) {
                        throw new Error('La subida ha caducado. Vuelve a elegir el archivo.');
                    }
                    lastError = new Error('Error del servidor al subir.');
                } catch (e) {
                    lastError = e;
                    if (e.message.includes('caducado')) throw e;
                }
                await new Promise((resolve) => setTimeout(resolve, 1000 * (attempt + 1)));
            }
            throw lastError;
        },

        async cancelUpload() {
            if (!this.token && !this.uploading) return;
            this.cancelled = true;
            const token = this.token;
            this.token = null;
            this.uploading = false;
            this.uploadDone = false;
            this.fileName = null;
            this.progress = 0;
            if (token) {
                await csrfFetch(config.cancelUrl.replace('__TOKEN__', token), { method: 'DELETE' }).catch(() => {});
            }
        },

        validate() {
            const form = this.$root;
            const errors = {};
            if (!form.elements.namedItem("title").value.trim()) errors.title = ['Escribe un título.'];
            if (this.categories.length === 0) errors.categories = ['Elige al menos una categoría.'];
            if (!config.editing) {
                if (this.mode === 'file' && !this.uploadDone && !this.uploading) errors.audio = ['Elige un archivo de audio.'];
                if (this.mode === 'youtube' && !form.elements.namedItem("youtube_url").value.trim()) errors.youtube_url = ['Pega el enlace de YouTube.'];
            }
            if (this.mode === 'file' && this.uploading) errors.audio = ['Espera a que termine de subirse el archivo.'];
            this.errors = errors;
            return Object.keys(errors).length === 0;
        },

        async submit() {
            if (this.submitting || !this.validate()) {
                this.$nextTick(() => this.$el.querySelector('[data-error]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                return;
            }

            this.submitting = true;
            const data = new FormData(this.$root);
            data.delete('categories[]');
            this.categories.forEach((id) => data.append('categories[]', id));
            if (this.mode === 'file') {
                data.delete('youtube_url');
                if (this.token && this.uploadDone) data.set('upload_token', this.token);
            } else {
                data.delete('upload_token');
            }

            try {
                const response = await csrfFetch(this.$root.action, { method: 'POST', body: data });
                const result = await response.json().catch(() => ({}));

                if (response.status === 422) {
                    this.errors = result.errors ?? {};
                    this.submitting = false;
                    this.$nextTick(() => this.$el.querySelector('[data-error]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                    return;
                }
                if (!response.ok) throw new Error();

                this.token = null;
                window.Turbo.visit(result.redirect);
            } catch {
                this.submitting = false;
                window.toast?.('No se ha podido guardar. Inténtalo de nuevo.', 'error');
            }
        },
    };
}
