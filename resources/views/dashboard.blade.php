<x-app-layout>
    <div class="py-8 bg-gray-50 min-h-screen">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg p-6">

                {{-- Mensajes --}}
                @if (session('success'))
                    <div class="mb-4 px-3 py-2 text-green-800 bg-green-100 border border-green-300 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 px-3 py-2 text-red-800 bg-red-100 border border-red-300 rounded">
                        <ul class="list-disc list-inside text-sm space-y-0.5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Estadísticas --}}
                <div class="mb-8 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="bg-purple-50 rounded-lg p-4 flex items-center space-x-3 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 12c2.21 0 4-1.79 4-4S14.21 4 12 4 8 5.79 8 8s1.79 4 4 4zM6 18c0-2.67 5.33-4 6-4s6 1.33 6 4v2H6v-2z" />
                        </svg>
                        <div>
                            <p class="text-xl font-semibold text-purple-700 leading-none">{{ $totalAudios }}</p>
                            <p class="text-purple-800 text-sm font-medium">Audios subidos</p>
                        </div>
                    </div>

                    <div class="bg-purple-50 rounded-lg p-4 flex items-center space-x-3 shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="currentColor" viewBox="0 0 24 24" stroke="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                        </svg>
                        <div>
                            <p class="text-xl font-semibold text-purple-700 leading-none">{{ $totalLikes }}</p>
                            <p class="text-purple-800 text-sm font-medium">Likes recibidos</p>
                        </div>
                    </div>
                </div>

                @php
                    function sortUrl($column, $table)
                    {
                        $sortParam = 'sort_' . $table;
                        $dirParam = 'direction_' . $table;

                        $direction = 'asc';
                        if(request($sortParam) === $column && request($dirParam) === 'asc') {
                            $direction = 'desc';
                        }

                        return route('dashboard', array_merge(request()->all(), [
                            $sortParam => $column,
                            $dirParam => $direction
                        ]));
                    }
                @endphp

                {{-- Formulario filtro y búsqueda para audios subidos --}}
                <section class="max-w-8xl mx-auto px-2 mb-4">
                    <h3 class="text-lg font-semibold mb-2 text-gray-900 flex items-center border-b border-gray-300 pb-1">
                        Tus archivos subidos
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-5 w-5 text-purple-500" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V7a2 2 0 00-2-2h-7l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0">
                        <input type="hidden" name="sort_uploaded" value="{{ request('sort_uploaded', 'created_at') }}">
                        <input type="hidden" name="direction_uploaded" value="{{ request('direction_uploaded', 'desc') }}">
                        <input type="hidden" name="sort_liked" value="{{ request('sort_liked', 'created_at') }}">
                        <input type="hidden" name="direction_liked" value="{{ request('direction_liked', 'desc') }}">

                        <select name="category_uploaded" class="border rounded px-3 py-1" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_uploaded') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <input 
                            type="search" 
                            name="search_uploaded" 
                            placeholder="Buscar en audios subidos" 
                            class="border rounded px-3 py-1 flex-grow"
                            value="{{ request('search_uploaded') ?? '' }}"
                            onkeypress="if(event.key === 'Enter') this.form.submit();"
                        />

                        <button type="submit" class="bg-purple-600 text-white rounded px-5 py-1 hover:bg-purple-700 transition whitespace-nowrap">
                            Buscar
                        </button>
                    </form>
                </section>

                {{-- Tabla audios subidos --}}
                <section class="max-w-8xl mx-auto px-2 overflow-x-auto rounded border border-gray-300 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('title', 'uploaded') }}">
                                        Título
                                        @if(request('sort_uploaded') == 'title')
                                            @if(request('direction_uploaded') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('category_name', 'uploaded') }}">
                                        Categorías
                                        @if(request('sort_uploaded') == 'category_name')
                                            @if(request('direction_uploaded') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('tag_name', 'uploaded') }}">
                                        Etiquetas
                                        @if(request('sort_uploaded') == 'tag_name')
                                            @if(request('direction_uploaded') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('created_at', 'uploaded') }}">
                                        Fecha
                                        @if(request('sort_uploaded') == 'created_at')
                                            @if(request('direction_uploaded') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($audios as $audio)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">{{ $audio->title }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        @foreach ($audio->categories as $category)
                                            <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold mr-1 px-2 py-0.5 rounded">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        @foreach ($audio->tags as $tag)
                                            <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold mr-1 px-2 py-0.5 rounded">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-500">{{ $audio->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <form action="{{ route('audios.edit', $audio) }}" method="GET" class="inline-block">
                                            @csrf
                                            <button type="submit" class="text-blue-600 hover:text-blue-900 font-semibold rounded px-2 py-1 transition duration-150">
                                                Editar
                                            </button>
                                        </form>
                                        <form action="{{ route('audios.destroy', $audio) }}" method="POST" onsubmit="return confirm('¿Eliminar este audio/video?');" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold rounded px-2 py-1 transition duration-150">
                                                Eliminar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500 italic">No tienes archivos subidos aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

                {{-- Formulario filtro y búsqueda para audios favoritos --}}
                <section class="max-w-7.5xl mx-auto px-2 mt-10 mb-4">
                    <h3 class="text-lg font-semibold mb-2 text-gray-900 flex items-center border-b border-gray-300 pb-1">
                        Tus archivos favoritos
                        <svg xmlns="http://www.w3.org/2000/svg" class="ml-1 h-5 w-5 text-purple-600" fill="currentColor" viewBox="0 0 24 24" stroke="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 
                                    4.42 3 7.5 3c1.74 0 3.41 0.81 4.5 2.09C13.09 3.81 
                                    14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 
                                    6.86-8.55 11.54L12 21.35z" />
                        </svg>
                    </h3>
                    <form method="GET" action="{{ route('dashboard') }}" class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-2 sm:space-y-0">
                        <input type="hidden" name="sort_uploaded" value="{{ request('sort_uploaded', 'created_at') }}">
                        <input type="hidden" name="direction_uploaded" value="{{ request('direction_uploaded', 'desc') }}">
                        <input type="hidden" name="sort_liked" value="{{ request('sort_liked', 'created_at') }}">
                        <input type="hidden" name="direction_liked" value="{{ request('direction_liked', 'desc') }}">

                        <select name="category_liked" class="border rounded px-3 py-1" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ request('category_liked') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>

                        <input 
                            type="search" 
                            name="search_liked" 
                            placeholder="Buscar en audios favoritos" 
                            class="border rounded px-3 py-1 flex-grow"
                            value="{{ request('search_liked') ?? '' }}"
                            onkeypress="if(event.key === 'Enter') this.form.submit();"
                        />

                        <button type="submit" class="bg-purple-600 text-white rounded px-5 py-1 hover:bg-purple-700 transition whitespace-nowrap">
                            Buscar
                        </button>
                    </form>
                </section>

                {{-- Tabla audios favoritos --}}
                <section class="max-w-7.5xl mx-auto px-2 overflow-x-auto rounded border border-gray-300 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('title', 'liked') }}">
                                        Título
                                        @if(request('sort_liked') == 'title')
                                            @if(request('direction_liked') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('category_name', 'liked') }}">
                                        Categorías
                                        @if(request('sort_liked') == 'category_name')
                                            @if(request('direction_liked') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('tag_name', 'liked') }}">
                                        Etiquetas
                                        @if(request('sort_liked') == 'tag_name')
                                            @if(request('direction_liked') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('user_name', 'liked') }}">
                                        Creado por
                                        @if(request('sort_liked') == 'user_name')
                                            @if(request('direction_liked') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">
                                    <a href="{{ sortUrl('created_at', 'liked') }}">
                                        Fecha
                                        @if(request('sort_liked') == 'created_at')
                                            @if(request('direction_liked') == 'asc') ▲ @else ▼ @endif
                                        @endif
                                    </a>
                                </th>
                                <th class="px-4 py-2 text-left font-medium text-gray-600 uppercase tracking-wide">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($likedAudios as $audio)
                                <tr>
                                    <td class="px-4 py-2 whitespace-nowrap font-medium text-gray-900">{{ $audio->title }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        @foreach ($audio->categories as $category)
                                            <span class="inline-block bg-indigo-100 text-indigo-800 text-xs font-semibold mr-1 px-2 py-0.5 rounded">
                                                {{ $category->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">
                                        @foreach ($audio->tags as $tag)
                                            <span class="inline-block bg-green-100 text-green-800 text-xs font-semibold mr-1 px-2 py-0.5 rounded">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-700">{{ $audio->user->name }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap text-gray-500">{{ $audio->created_at->format('Y-m-d') }}</td>
                                    <td class="px-4 py-2 whitespace-nowrap">
                                        <a href="{{ route('audios.download', $audio) }}" class="text-indigo-600 hover:text-indigo-900 font-semibold px-2 py-1 rounded transition">
                                            Descargar
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-6 text-center text-gray-500 italic">No tienes archivos marcados como favoritos aún.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </section>

            </div>
        </div>
    </div>

    {{-- Remix Icons CDN --}}
    <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />

    <script src="//unpkg.com/alpinejs" defer></script>
</x-app-layout>
