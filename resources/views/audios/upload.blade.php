<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-8 max-w-xl mx-auto" x-data="fileSelector()">

                {{-- Alerta éxito Laravel --}}
                @if (session('success'))
                    <div 
                        x-show="showSuccess"
                        x-init="setTimeout(() => showSuccess = false, 5000)"
                        class="mb-6 px-4 py-3 text-green-800 bg-green-100 border border-green-300 rounded-md transition-opacity duration-700"
                        role="alert"
                    >
                        {{ session('success') }}
                    </div>
                @endif

                {{-- Alerta error Laravel --}}
                @if ($errors->any())
                    <div 
                        x-show="showError"
                        x-init="setTimeout(() => showError = false, 7000)"
                        class="mb-6 px-4 py-3 text-red-800 bg-red-100 border border-red-300 rounded-md transition-opacity duration-700"
                        role="alert"
                    >
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Texto informativo --}}
                <div class="mb-8 text-gray-700 text-sm leading-relaxed">
                    <p><strong>Sube tus archivos de audio.</strong></p>
                    <p>Para obtener la mejor calidad, utiliza WAV, FLAC, AIFF o ALAC. El tamaño máximo del archivo es de 4 GB sin comprimir. <a href="#" class="text-indigo-600 hover:underline">Más información.</a></p>
                </div>

                {{-- Formulario subida audio/video --}}
                <form 
                    action="{{ route('audios.store') }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    class="space-y-6"
                    onsubmit="return validarFormulario()"
                    x-data="{
                        selectedCategories: {{ json_encode(old('categories', [])) }},
                        toggleCategory(id) {
                            this.selectedCategories = this.selectedCategories.includes(id) 
                                ? this.selectedCategories.filter(i => i !== id) 
                                : [id]; // Solo una categoría activa
                        }
                    }"
                >
                    @csrf

                    {{-- Título --}}
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">
                            Título <span class="text-red-600">*</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            id="title"
                            required
                            placeholder="Introduce el título del audio o video"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            value="{{ old('title') }}"
                        >
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 mb-1">Descripción (opcional)</label>
                        <textarea
                            name="description"
                            id="description"
                            rows="3"
                            placeholder="Agrega una descripción breve"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm resize-none"
                        >{{ old('description') }}</textarea>
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Categoría <span class="text-red-600">*</span></label>
                        <div class="flex flex-wrap gap-2 border border-gray-300 rounded-md p-2 mb-3">
                            @foreach ($categories as $category)
                                <button
                                    type="button"
                                    @click="toggleCategory({{ $category->id }})"
                                    :class="selectedCategories.includes({{ $category->id }}) 
                                        ? 'bg-indigo-600 text-white shadow-md' 
                                        : 'bg-white text-gray-700 hover:bg-indigo-100'"
                                    class="relative px-4 py-1 rounded-md transition-colors duration-200 focus:outline-none"
                                    aria-pressed="selectedCategories.includes({{ $category->id }}) ? 'true' : 'false'"
                                >
                                    {{ $category->name }}
                                    {{-- Cruz para desmarcar --}}
                                    <template x-if="selectedCategories.includes({{ $category->id }})">
                                        <span
                                            @click.stop="toggleCategory({{ $category->id }})"
                                            class="absolute -top-2 -right-2 cursor-pointer text-white bg-indigo-800 rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold hover:bg-indigo-700 transition"
                                            title="Eliminar selección"
                                        >&times;</span>
                                    </template>
                                </button>
                            @endforeach
                        </div>
                        {{-- Input oculto para enviar categoría seleccionada --}}
                        <template x-for="id in selectedCategories" :key="id">
                            <input type="hidden" name="categories[]" :value="id">
                        </template>
                    </div>

                    {{-- Etiquetas --}}
                    <div>
                        <label for="tags" class="block text-sm font-semibold text-gray-700 mb-1">Etiquetas (separadas por comas)</label>
                        <input
                            type="text"
                            name="tags"
                            id="tags"
                            placeholder="Ejemplo: rock, indie, pop"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            value="{{ old('tags') }}"
                        >
                    </div>

                    {{-- Privacidad --}}
                    <div>
                        <label for="privacy" class="block text-sm font-semibold text-gray-700 mb-1">Privacidad</label>
                        <select
                            name="privacy"
                            id="privacy"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="public" {{ old('privacy') === 'public' ? 'selected' : '' }}>Público</option>
                            <option value="private" {{ old('privacy') === 'private' ? 'selected' : '' }}>Privado</option>
                        </select>
                    </div>

                    {{-- Archivo --}}
                    <div>
                        <div class="mb-6" x-data="fileInput()">
                            <label for="audio" class="block text-sm font-semibold text-gray-700 mb-3">
                                Selecciona un archivo (mp3, wav, m4a, mp4) <span class="text-red-600">*</span>
                            </label>

                            <div class="flex items-center space-x-1">
                                <template x-if="!fileSelected">
                                    <label for="audio"
                                        class="cursor-pointer inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-semibold rounded-md
                                            shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"
                                    >
                                        Seleccionar Archivo
                                    </label>

                                </template>

                                <template x-if="fileSelected">
                                    <div class="flex items-center bg-indigo-100 text-indigo-900 px-4 py-2 rounded-md shadow-md max-w-xs">
                                        <span class="truncate pr-4" x-text="fileName"></span>
                                        <button type="button" @click="clearFile" title="Eliminar archivo"
                                            class="text-indigo-700 hover:text-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded ml-auto"
                                            style="font-size: 1.25rem; line-height: 1; font-weight: bold;"
                                        >&times;</button>
                                    </div>
                                </template>

                                <input
                                    type="file"
                                    name="audio"
                                    id="audio"
                                    accept=".mp3,.wav,.m4a,.mp4"
                                    class="hidden"
                                    @change="updateFile($event)"
                                    x-ref="inputFile"
                                >
                            </div>
                        </div>
                        {{-- Fin del control personalizado con Alpine --}}
                    </div>

                    {{-- Enlace YouTube --}}
                    <div>
                        <label for="youtube_url" class="block text-sm font-semibold text-gray-700 mb-1">
                            Enlace de YouTube (opcional)
                        </label>
                        <input
                            type="url"
                            name="youtube_url"
                            id="youtube_url"
                            placeholder="https://www.youtube.com/watch?v=XXXXXXX"
                            value="{{ old('youtube_url') }}"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                   focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </div>

                    {{-- Imagen portada (opcional) --}}
                    <div>
                        <label for="cover" class="block text-sm font-semibold text-gray-700 mb-1">Imagen portada (opcional)</label>
                        <input
                            type="file"
                            name="cover"
                            id="cover"
                            accept="image/*"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm
                                focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </div>

                    {{-- Botón enviar --}}


                    <button
                        type="submit"
                        class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-md shadow-md
                               hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"
                    >
                        Subir Archivo / Video
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- SweetAlert2 CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        function fileInput() {
            return {
                fileSelected: false,
                fileName: '',
                updateFile(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.fileSelected = true;
                        this.fileName = file.name;
                    } else {
                        this.clearFile();
                    }
                },
                clearFile() {
                    this.fileSelected = false;
                    this.fileName = '';
                    this.$refs.inputFile.value = '';
                }
            }
        }
    </script>

    {{-- Script validar formulario con SweetAlert2 --}}
    <script>
        function validarFormulario() {
            const title = document.getElementById('title').value.trim();
            const audioInput = document.getElementById('audio');
            const audio = audioInput.files.length;
            const youtubeUrl = document.getElementById('youtube_url').value.trim();

            if (!title) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Título requerido',
                    text: 'Por favor, introduce un título.',
                });
                return false;
            }

            if (!audio && !youtubeUrl) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Archivo o enlace requerido',
                    text: 'Por favor, selecciona un archivo o pega un enlace de YouTube.',
                });
                return false;
            }

            if (youtubeUrl && !/^https?:\/\/(www\.)?youtube\.com\/watch\?v=[\w-]+/.test(youtubeUrl)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Enlace inválido',
                    text: 'Por favor, introduce un enlace válido de YouTube.',
                });
                return false;
            }

            return true;
        }
    </script>

    {{-- Remix Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />

    <script src="//unpkg.com/alpinejs" defer></script>
</x-app-layout>
