<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-lg rounded-lg p-8 max-w-xl mx-auto">

                {{-- Mensaje de éxito o error --}}
                @if (session('success'))
                    <div class="mb-4 px-4 py-2 text-green-800 bg-green-100 border border-green-300 rounded-md">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 px-4 py-2 text-red-800 bg-red-100 border border-red-300 rounded-md">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Título --}}
                <h3 class="text-2xl font-semibold text-gray-900 mb-6">Editar Audio</h3>

                {{-- Formulario --}}
                <form method="POST" action="{{ route('audios.update', $audio) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    {{-- Título --}}
                    <div class="mb-6">
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                        <input type="text" name="title" id="title" required placeholder="Título del audio"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            value="{{ old('title', $audio->title) }}">
                    </div>

                    {{-- Descripción --}}
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                        <textarea name="description" id="description" rows="4" placeholder="Descripción breve"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description', $audio->description) }}</textarea>
                    </div>

                    {{-- Categorías --}}
                    <div class="mb-6">
                        <label for="categories" class="block text-sm font-medium text-gray-700 mb-1">Categorías</label>
                        <select name="categories[]" id="categories" multiple
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" 
                                    @selected(in_array($category->id, old('categories', $audio->categories->pluck('id')->toArray())))>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Etiquetas --}}
                    <div class="mb-6">
                        <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Etiquetas (separadas por comas)</label>
                        <input type="text" name="tags" id="tags" placeholder="Ejemplo: rock, indie, pop"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            value="{{ old('tags', $audio->tags->pluck('name')->implode(', ')) }}">
                    </div>

                    {{-- Enlace YouTube --}}
                    <div class="mb-6">
                        <label for="youtube_url" class="block text-sm font-medium text-gray-700 mb-1">Enlace de YouTube (opcional)</label>
                        <input type="url" name="youtube_url" id="youtube_url" placeholder="https://www.youtube.com/watch?v=XXXXXXX"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            value="{{ old('youtube_url', $audio->youtube_url) }}">
                    </div>

                    {{-- Imagen de portada --}}
                    <div class="mb-6">
                        <label for="cover" class="block text-sm font-medium text-gray-700 mb-1">Imagen de portada (opcional)</label>
                        <input type="file" name="cover" id="cover" accept="image/*"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @if ($audio->cover)
                            <div class="mt-2">
                                <img src="{{ Storage::url($audio->cover) }}" alt="Cover Image" class="w-32 h-32 object-cover rounded-md">
                            </div>
                        @endif
                    </div>

                    {{-- Privacidad --}}
                    <div class="mb-6">
                        <label for="privacy" class="block text-sm font-medium text-gray-700 mb-1">Privacidad</label>
                        <select name="privacy" id="privacy"
                            class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="public" @selected(old('privacy', $audio->privacy) === 'public')>Público</option>
                            <option value="private" @selected(old('privacy', $audio->privacy) === 'private')>Privado</option>
                        </select>
                    </div>

                    {{-- Botón de actualización --}}
                    <div class="mt-6">
                        <button type="submit"
                            class="w-full py-3 bg-indigo-600 text-white font-semibold rounded-md shadow-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            Actualizar Audio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
