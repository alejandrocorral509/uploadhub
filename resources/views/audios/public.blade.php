<x-app-layout>
    <div class="py-12 bg-gray-50 min-h-screen">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 bg-white rounded-lg shadow-md p-8">

            <h1 class="text-3xl font-bold mb-8 text-center text-gray-900">Audios Públicos</h1>

            @if($audios->count())
                <div class="space-y-6">
                    @foreach($audios as $audio)
                        <article class="border border-gray-200 rounded-md p-6 hover:shadow-lg transition-shadow duration-200">
                            <header class="mb-2">
                                <h2 class="font-semibold text-xl text-gray-800">{{ $audio->titulo }}</h2>
                                <p class="text-xs text-gray-500">Subido por {{ $audio->user->name }}</p>
                            </header>

                            <p class="text-gray-700 mb-4">{{ $audio->description }}</p>

                            <div class="flex flex-wrap gap-2 mb-4">
                                @foreach($audio->categories as $category)
                                    <span class="bg-indigo-100 text-indigo-800 text-xs font-semibold px-3 py-1 rounded-full">
                                        {{ $category->name }}
                                    </span>
                                @endforeach
                                @foreach($audio->tags as $tag)
                                    <span class="bg-green-100 text-green-800 text-xs font-semibold px-3 py-1 rounded-full">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>

                            <div class="mb-4">
                                @if (Str::startsWith($audio->filetype, 'audio'))
                                    <audio controls class="w-full rounded-md" preload="none" style="height: 36px;">
                                        <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                        Tu navegador no soporta la reproducción de audio.
                                    </audio>
                                @elseif (Str::startsWith($audio->filetype, 'video'))
                                    <video controls class="w-full rounded-md" preload="metadata" style="max-height: 200px;">
                                        <source src="{{ asset('storage/' . $audio->archivo) }}" type="{{ $audio->filetype }}">
                                        Tu navegador no soporta la reproducción de video.
                                    </video>
                                @endif
                            </div>

                            {{-- Comentarios --}}
                            <section>
                                <h3 class="text-lg font-semibold mb-3 text-gray-800">Comentarios</h3>

                                @auth
                                <form action="{{ route('comments.store', $audio) }}" method="POST" class="mb-4">
                                    @csrf
                                    <textarea
                                        name="content"
                                        rows="3"
                                        required
                                        placeholder="Añade un comentario..."
                                        class="w-full rounded border border-gray-300 p-2 resize-none focus:ring-indigo-500 focus:border-indigo-500"
                                    ></textarea>
                                    <button type="submit" class="mt-2 px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700 transition">Comentar</button>
                                </form>
                                @else
                                    <p class="text-sm text-gray-500 italic">Debes <a href="{{ route('login') }}" class="text-indigo-600 underline">iniciar sesión</a> para comentar.</p>
                                @endauth

                                @forelse ($audio->comments as $comment)
                                    <div class="border border-gray-200 rounded p-3 mb-2 bg-gray-50">
                                        <p class="text-sm text-gray-800">{{ $comment->content }}</p>
                                        <p class="text-xs text-gray-500 mt-1">Por {{ $comment->user->name }} - {{ $comment->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <p class="text-gray-500 italic text-sm">No hay comentarios aún.</p>
                                @endforelse
                            </section>
                        </article>
                    @endforeach
                </div>

                <div class="mt-8">
                    {{ $audios->links() }}
                </div>
            @else
                <p class="text-center text-gray-500 text-sm">No hay audios públicos disponibles.</p>
            @endif

        </div>
    </div>
</x-app-layout>
