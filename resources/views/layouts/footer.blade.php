<footer class="mt-16 border-t border-gray-200 bg-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:px-8">
        <div>
            <a href="{{ route('home') }}" class="text-lg font-extrabold tracking-tight text-brand-700">UploadHub</a>
            <p class="mt-2 max-w-xs text-sm text-gray-500">Sube tus podcasts, compártelos con un feed RSS y descubre qué escucha tu audiencia.</p>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Descubrir</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li><a href="{{ route('explore') }}" class="hover:text-brand-700">Explorar episodios</a></li>
                <li><a href="{{ route('shows.index') }}" class="hover:text-brand-700">Programas</a></li>
                <li><a href="{{ route('explore', ['orden' => 'escuchados']) }}" class="hover:text-brand-700">Lo más escuchado</a></li>
            </ul>
        </div>
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Crear</h2>
            <ul class="mt-3 space-y-2 text-sm text-gray-600">
                <li><a href="{{ route('episodes.create') }}" class="hover:text-brand-700">Subir un episodio</a></li>
                <li><a href="{{ route('shows.create') }}" class="hover:text-brand-700">Crear un programa</a></li>
                <li><a href="{{ route('stats.index') }}" class="hover:text-brand-700">Estadísticas</a></li>
            </ul>
        </div>
    </div>
    <div class="border-t border-gray-100 py-4 text-center text-xs text-gray-400">
        &copy; {{ date('Y') }} UploadHub · Proyecto de Alejandro Corral Carrasco
    </div>
</footer>
