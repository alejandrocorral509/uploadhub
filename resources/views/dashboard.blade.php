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

    {{-- Remix Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />

    <script src="//unpkg.com/alpinejs" defer></script>
</x-app-layout>
