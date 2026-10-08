@php
    $editing = $audio->exists;
    $config = [
        'editing' => $editing,
        'mode' => $audio->isYoutube() ? 'youtube' : 'file',
        'categories' => $audio->exists ? $audio->categories->pluck('id')->all() : [],
        'extensions' => config('media.allowed_extensions'),
        'maxMb' => config('media.max_upload_mb'),
        'startUrl' => route('uploads.start'),
        'chunkUrl' => route('uploads.chunk', '__TOKEN__'),
        'cancelUrl' => route('uploads.cancel', '__TOKEN__'),
    ];
@endphp

<x-app-layout :title="$editing ? 'Editar episodio' : 'Subir episodio'">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $editing ? 'Editar episodio' : 'Subir un episodio' }}</h1>
        <p class="mt-1 text-sm text-gray-500">
            @if ($editing)
                <a href="{{ $audio->url() }}" class="link">{{ $audio->title }}</a>
            @else
                El archivo empieza a subirse en cuanto lo eliges; mientras tanto, rellena el resto.
            @endif
        </p>

        <form x-data="episodeForm(@js($config))" @submit.prevent="submit" novalidate
              action="{{ $editing ? route('episodes.update', $audio) : route('episodes.store') }}" method="POST" enctype="multipart/form-data"
              class="mt-8 space-y-8">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            {{-- 1. Audio --}}
            <section class="card p-6">
                <h2 class="font-bold">1. El audio</h2>
                @if ($editing)
                    <p class="mt-1 text-sm text-gray-500">
                        Ahora mismo: {{ $audio->isYoutube() ? 'vídeo de YouTube' : 'archivo subido' }}{{ $audio->formattedDuration() ? ' ('.$audio->formattedDuration().')' : '' }}.
                        Solo tienes que tocar esto si quieres cambiarlo.
                    </p>
                @endif

                <div class="mt-4 inline-flex rounded-lg bg-gray-100 p-1 text-sm font-medium" role="tablist">
                    <button type="button" role="tab" @click="mode = 'file'" :aria-selected="mode === 'file'"
                            class="rounded-md px-4 py-1.5" :class="mode === 'file' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'">
                        <i class="fa-solid fa-file-audio"></i> Archivo
                    </button>
                    <button type="button" role="tab" @click="mode = 'youtube'" :aria-selected="mode === 'youtube'"
                            class="rounded-md px-4 py-1.5" :class="mode === 'youtube' ? 'bg-white shadow-sm text-gray-900' : 'text-gray-500'">
                        <i class="fa-brands fa-youtube"></i> YouTube
                    </button>
                </div>

                <div x-show="mode === 'file'" class="mt-4">
                    <label x-show="! fileName" for="audio-file"
                           class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 px-6 py-10 text-center transition hover:border-brand-400 hover:bg-brand-50/40"
                           @dragover.prevent @drop.prevent="pickFile({ target: { files: $event.dataTransfer.files, set value(v) {} } })">
                        <i class="fa-solid fa-cloud-arrow-up text-3xl text-brand-500"></i>
                        <span class="mt-3 font-semibold text-gray-900">Elige un archivo o arrástralo aquí</span>
                        <span class="mt-1 text-xs text-gray-500">{{ strtoupper(implode(', ', config('media.allowed_extensions'))) }} · hasta {{ config('media.max_upload_mb') }} MB</span>
                    </label>
                    <input id="audio-file" type="file" class="sr-only" accept="{{ collect(config('media.allowed_extensions'))->map(fn ($e) => '.'.$e)->implode(',') }},audio/*" @change="pickFile">

                    <div x-show="fileName" x-cloak class="rounded-xl border border-gray-200 p-4">
                        <div class="flex items-center gap-3">
                            <i class="fa-solid fa-file-audio text-2xl text-brand-500"></i>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold" x-text="fileName"></p>
                                <p class="text-xs text-gray-500">
                                    <span x-text="formatSize(fileSize)"></span> ·
                                    <span x-show="uploading">Subiendo… <span x-text="progress + '%'"></span></span>
                                    <span x-show="uploadDone" class="font-semibold text-emerald-600"><i class="fa-solid fa-check"></i> Subido</span>
                                </p>
                            </div>
                            <button type="button" @click="cancelUpload()" class="btn btn-ghost btn-sm" x-text="uploading ? 'Cancelar' : 'Cambiar'"></button>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-brand-600 transition-[width]" :class="uploadDone && 'bg-emerald-500'" :style="`width: ${progress}%`"></div>
                        </div>
                    </div>
                    <p x-show="uploadError" x-cloak x-text="uploadError" class="field-error" data-error></p>
                </div>

                <div x-show="mode === 'youtube'" x-cloak class="mt-4">
                    <label for="youtube_url" class="label">Enlace del vídeo</label>
                    <input id="youtube_url" name="youtube_url" type="url" class="input" value="{{ old('youtube_url', $audio->youtube_url) }}"
                           placeholder="https://www.youtube.com/watch?v=… o https://youtu.be/…" @input="delete errors.youtube_url">
                    <p class="help">Vale cualquier enlace de YouTube: normal, corto, de móvil o de Shorts.</p>
                    <p class="field-error" x-show="error('youtube_url')" x-text="error('youtube_url')" :data-error="error('youtube_url') ? 1 : null"></p>
                </div>

                <p class="field-error" x-show="error('audio')" x-text="error('audio')" :data-error="error('audio') ? 1 : null"></p>
            </section>

            {{-- 2. Información --}}
            <section class="card space-y-5 p-6">
                <h2 class="font-bold">2. Información</h2>

                <div>
                    <label for="title" class="label">Título <span class="text-red-600">*</span></label>
                    <input id="title" name="title" type="text" maxlength="255" required class="input" value="{{ old('title', $audio->title) }}" @input="delete errors.title">
                    <p class="field-error" x-show="error('title')" x-text="error('title')" :data-error="error('title') ? 1 : null"></p>
                </div>

                <div>
                    <label for="description" class="label">Descripción</label>
                    <textarea id="description" name="description" rows="5" maxlength="5000" class="input" placeholder="De qué trata, invitados, enlaces que mencionas…">{{ old('description', $audio->description) }}</textarea>
                    <p class="field-error" x-show="error('description')" x-text="error('description')"></p>
                </div>

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-3">
                        <label for="show_id" class="label">Programa</label>
                        <select id="show_id" name="show_id" class="input">
                            <option value="">Episodio suelto (sin programa)</option>
                            @foreach ($shows as $show)
                                <option value="{{ $show->id }}" @selected($selectedShow === $show->id)>{{ $show->title }}</option>
                            @endforeach
                        </select>
                        <p class="help">Los episodios de un programa aparecen en su feed RSS. <a href="{{ route('shows.create') }}" class="link">Crear un programa</a></p>
                        <p class="field-error" x-show="error('show_id')" x-text="error('show_id')"></p>
                    </div>
                    <div>
                        <label for="season" class="label">Temporada</label>
                        <input id="season" name="season" type="number" min="1" class="input" value="{{ old('season', $audio->season) }}">
                    </div>
                    <div>
                        <label for="episode_number" class="label">Nº de episodio</label>
                        <input id="episode_number" name="episode_number" type="number" min="1" class="input" value="{{ old('episode_number', $audio->episode_number) }}">
                    </div>
                </div>

                <div>
                    <span class="label">Categorías <span class="text-red-600">*</span> <span class="font-normal text-gray-500">(hasta 3)</span></span>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($categories as $category)
                            <button type="button" @click="toggleCategory({{ $category->id }})" class="chip"
                                    :class="categories.includes({{ $category->id }}) && 'chip-active'" :aria-pressed="categories.includes({{ $category->id }})">
                                {{ $category->name }}
                            </button>
                        @endforeach
                    </div>
                    <p class="field-error" x-show="error('categories')" x-text="error('categories')" :data-error="error('categories') ? 1 : null"></p>
                </div>

                <div>
                    <label for="tags" class="label">Etiquetas</label>
                    <input id="tags" name="tags" type="text" class="input" value="{{ old('tags', $audio->exists ? $audio->tags->pluck('name')->implode(', ') : '') }}" placeholder="laravel, entrevistas, música indie">
                    <p class="help">Separadas por comas. Hasta 10.</p>
                    <p class="field-error" x-show="error('tags')" x-text="error('tags')"></p>
                </div>
            </section>

            {{-- 3. Portada y privacidad --}}
            <section class="card space-y-5 p-6">
                <h2 class="font-bold">3. Portada y privacidad</h2>

                <div x-data="imagePreview(@js($audio->cover ? $audio->coverUrl() : null))" class="flex items-center gap-4">
                    <template x-if="preview"><img :src="preview" alt="Vista previa de la portada" class="size-24 rounded-xl object-cover"></template>
                    <template x-if="! preview">
                        <div class="flex size-24 items-center justify-center rounded-xl bg-gray-100 text-gray-400"><i class="fa-regular fa-image text-2xl"></i></div>
                    </template>
                    <div>
                        <label class="btn btn-secondary btn-sm cursor-pointer">
                            <i class="fa-solid fa-image"></i> Elegir imagen
                            <input x-ref="input" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick">
                        </label>
                        <button type="button" x-show="preview" @click="clear()" class="btn btn-ghost btn-sm text-red-600">Quitar</button>
                        <input type="hidden" name="remove_cover" :value="remove ? 1 : 0">
                        <p class="help">JPG, PNG o WebP, hasta 5 MB. Si no pones ninguna, se usa la del programa.</p>
                    </div>
                </div>
                <p class="field-error" x-show="error('cover')" x-text="error('cover')"></p>

                <fieldset>
                    <legend class="label">Visibilidad</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach (['public' => ['fa-earth-europe', 'Público', 'Aparece en Explorar, en el feed RSS y cualquiera puede escucharlo.'], 'private' => ['fa-lock', 'Privado', 'Solo lo ves y lo escuchas tú. Útil para borradores.']] as $value => [$icon, $label, $text])
                            <label class="flex cursor-pointer gap-3 rounded-xl border border-gray-200 p-4 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50">
                                <input type="radio" name="privacy" value="{{ $value }}" class="mt-1 accent-brand-600" @checked(old('privacy', $audio->privacy ?? 'public') === $value)>
                                <span>
                                    <span class="flex items-center gap-2 text-sm font-semibold"><i class="fa-solid {{ $icon }} text-gray-400"></i> {{ $label }}</span>
                                    <span class="mt-1 block text-xs text-gray-500">{{ $text }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ $editing ? $audio->url() : route('library') }}" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg" :disabled="submitting">
                    <i class="fa-solid" :class="submitting ? 'fa-spinner fa-spin' : 'fa-check'"></i>
                    <span x-text="submitting ? 'Guardando…' : (uploading ? 'Esperando a que termine la subida…' : '{{ $editing ? 'Guardar cambios' : 'Publicar episodio' }}')"></span>
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
