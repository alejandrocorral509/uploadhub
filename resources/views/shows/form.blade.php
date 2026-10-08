@php($editing = $show->exists)

<x-app-layout :title="$editing ? 'Editar programa' : 'Crear programa'">
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6">
        <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $editing ? 'Editar programa' : 'Crear un programa' }}</h1>
        <p class="mt-1 text-sm text-gray-500">Un programa agrupa tus episodios y tiene su propio feed RSS para Spotify, Apple Podcasts o cualquier app.</p>

        <form method="POST" action="{{ $editing ? route('shows.update', $show) : route('shows.store') }}" enctype="multipart/form-data" class="card mt-8 space-y-6 p-6">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif

            <div>
                <label for="title" class="label">Nombre del programa <span class="text-red-600">*</span></label>
                <input id="title" name="title" type="text" maxlength="120" required class="input" value="{{ old('title', $show->title) }}">
                @error('title') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="label">Descripción</label>
                <textarea id="description" name="description" rows="5" maxlength="4000" class="input" placeholder="De qué va, cada cuánto sale, quién lo presenta…">{{ old('description', $show->description) }}</textarea>
                @error('description') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category_id" class="label">Categoría</label>
                    <select id="category_id" name="category_id" class="input">
                        <option value="">Sin categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $show->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="language" class="label">Idioma</label>
                    <select id="language" name="language" class="input">
                        @foreach ($languages as $code => $name)
                            <option value="{{ $code }}" @selected(old('language', $show->language) === $code)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <label class="flex items-center gap-3 text-sm">
                <input type="hidden" name="explicit" value="0">
                <input type="checkbox" name="explicit" value="1" class="size-4 accent-brand-600" @checked(old('explicit', $show->explicit))>
                Contenido explícito (lenguaje adulto). Las apps de podcasts lo marcan así.
            </label>

            <div x-data="imagePreview(@js($show->coverUrl()))" class="flex items-center gap-4">
                <template x-if="preview"><img :src="preview" alt="Vista previa de la portada" class="size-28 rounded-xl object-cover"></template>
                <template x-if="! preview">
                    <div class="flex size-28 items-center justify-center rounded-xl bg-gray-100 text-gray-400"><i class="fa-regular fa-image text-2xl"></i></div>
                </template>
                <div>
                    <label class="btn btn-secondary btn-sm cursor-pointer">
                        <i class="fa-solid fa-image"></i> Elegir portada
                        <input x-ref="input" type="file" name="cover" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="pick">
                    </label>
                    <button type="button" x-show="preview" @click="clear()" class="btn btn-ghost btn-sm text-red-600">Quitar</button>
                    <input type="hidden" name="remove_cover" :value="remove ? 1 : 0">
                    <p class="help">Cuadrada, mínimo 300×300 px. Spotify y Apple piden 1400×1400 o más.</p>
                    @error('cover') <p class="field-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-100 pt-6">
                <a href="{{ $editing ? route('shows.show', $show) : route('library', ['tab' => 'programas']) }}" class="btn btn-secondary">Cancelar</a>
                <button class="btn btn-primary">{{ $editing ? 'Guardar cambios' : 'Crear programa' }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
