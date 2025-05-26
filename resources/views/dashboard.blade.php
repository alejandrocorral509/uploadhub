<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8"> {{-- Contenedor más ancho --}}
            <div class="bg-white shadow-lg rounded-lg p-8">

                {{-- Mensajes de estado --}}
                @if (session('success'))
                    <div class="mb-6 px-4 py-3 text-green-800 bg-green-100 border border-green-300 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 px-4 py-3 text-red-800 bg-red-100 border border-red-300 rounded-md">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Formulario subida audio/video --}}
                <form 
                    action="{{ route('audios.store') }}" 
                    method="POST" 
                    enctype="multipart/form-data" 
                    class="space-y-6 max-w-xl mx-auto" 
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
                        <label for="audio" class="block text-sm font-semibold text-gray-700 mb-3">
                            Selecciona un archivo (mp3, wav, m4a, mp4) <span class="text-red-600">*</span>
                        </label>
                        <div class="flex items-center space-x-4">
                            <label for="audio"
                                class="cursor-pointer inline-flex items-center px-5 py-2 bg-indigo-600 text-white font-semibold rounded-md
                                       shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"
                            >
                                Seleccionar Archivo
                            </label>
                            <span id="file-name" class="text-gray-600 italic truncate max-w-xs select-none">Ningún archivo seleccionado</span>
                        </div>
                        <input
                            type="file"
                            name="audio"
                            id="audio"
                            accept=".mp3,.wav,.m4a,.mp4"
                            required
                            class="hidden"
                        >
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-md shadow-md
                               hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"
                    >
                        Subir Archivo
                    </button>
                </form>

                {{-- Lista de audios subidos --}}
                    <section class="mt-12 max-w-7xl mx-auto px-4"> {{-- Contenedor más ancho para reproductores --}}
                        <h3 class="text-xl font-semibold mb-6 text-gray-900 border-b border-gray-300 pb-2">Tus archivos subidos</h3>
                        <div class="overflow-x-auto rounded-md border border-gray-300 shadow-sm">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Categorías</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Etiquetas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha de subida</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reproducción</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @forelse ($audios as $audio)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $audio->titulo }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                @foreach ($audio->categories as $category)
                                                    <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">
                                                        {{ $category->name }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                                                @foreach ($audio->tags as $tag)
                                                    <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded">
                                                        {{ $tag->name }}
                                                    </span>
                                                @endforeach
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $audio->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 w-[280px] min-w-[280px]"> {{-- ancho fijo mínimo --}}
                                                @if (\Illuminate\Support\Str::startsWith($audio->filetype, 'audio'))
                                                    <audio controls class="w-full rounded-md shadow-sm" preload="none" style="min-width:280px;">
                                                        <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                                        Tu navegador no soporta la reproducción de audio.
                                                    </audio>
                                                @elseif (\Illuminate\Support\Str::startsWith($audio->filetype, 'video'))
                                                    <video controls class="w-full rounded-md shadow-sm" preload="metadata" style="min-width:280px;">
                                                        <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                                        Tu navegador no soporta la reproducción de video.
                                                    </video>
                                                @else
                                                    <span class="text-gray-500 italic">Archivo no reproducible</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <form action="{{ route('audios.destroy', $audio) }}" method="POST" onsubmit="return confirm('¿Eliminar este audio/video?');" class="inline-block">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900 font-semibold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 rounded px-2 py-1 transition duration-150">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-6 text-center text-gray-500 italic">No tienes podcasts subidos aún.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>



            </div>
        </div>
    </div>

    {{-- Script para mostrar nombre archivo seleccionado --}}
    <script>
        const inputFile = document.getElementById('audio');
        const fileName = document.getElementById('file-name');

        inputFile.addEventListener('change', () => {
            fileName.textContent = inputFile.files.length > 0 ? inputFile.files[0].name : 'Ningún archivo seleccionado';
        });

        // Validación simple antes de enviar el formulario
        function validarFormulario() {
            const title = document.getElementById('title').value.trim();
            const audio = document.getElementById('audio').files.length;

            if (!title) {
                alert('Por favor, introduce un título.');
                return false;
            }
            if (!audio) {
                alert('Por favor, selecciona un archivo.');
                return false;
            }
            return true;
        }
    </script>

    {{-- Remix Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />

    <script src="//unpkg.com/alpinejs" defer></script>
    
</x-app-layout>
