@php
    $links = [
        ['route' => 'home', 'label' => 'Inicio', 'icon' => 'fa-house', 'active' => 'home'],
        ['route' => 'explore', 'label' => 'Explorar', 'icon' => 'fa-compass', 'active' => 'explore'],
        ['route' => 'shows.index', 'label' => 'Programas', 'icon' => 'fa-microphone-lines', 'active' => 'shows.*'],
    ];
    if (auth()->check()) {
        $links[] = ['route' => 'library', 'label' => 'Biblioteca', 'icon' => 'fa-book-open', 'active' => 'library'];
    }
@endphp

<nav x-data="{ open: false }" class="sticky top-0 z-40 border-b border-gray-200 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center gap-4 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2">
            <img src="{{ asset('images/favICON.png') }}" alt="" class="h-9 w-auto">
            <span class="text-lg font-extrabold tracking-tight text-brand-700">UploadHub</span>
        </a>

        <div class="hidden items-center gap-1 lg:flex">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @class([
                       'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                       'bg-brand-50 text-brand-700' => request()->routeIs($link['active']),
                       'text-gray-600 hover:bg-gray-100 hover:text-gray-900' => ! request()->routeIs($link['active']),
                   ])>
                    <i class="fa-solid {{ $link['icon'] }}"></i> {{ $link['label'] }}
                </a>
            @endforeach
        </div>

        <form action="{{ route('explore') }}" method="GET" class="ml-auto hidden max-w-xs flex-1 md:block" role="search">
            <label for="nav-search" class="sr-only">Buscar episodios</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-gray-400"></i>
                <input id="nav-search" type="search" name="q" value="{{ request()->routeIs('explore') ? request('q') : '' }}"
                       placeholder="Buscar episodios, programas, temas…" class="input rounded-full pl-9">
            </div>
        </form>

        <div class="ml-auto flex items-center gap-2 md:ml-0">
            @auth
                <a href="{{ route('episodes.create') }}" class="btn btn-primary hidden sm:inline-flex">
                    <i class="fa-solid fa-cloud-arrow-up"></i> Subir
                </a>

                <div x-data="{ menu: false }" class="relative hidden sm:block" @keydown.escape.window="menu = false">
                    <button type="button" @click="menu = ! menu" class="flex items-center gap-2 rounded-full p-1 pr-2 transition hover:bg-gray-100"
                            :aria-expanded="menu" aria-haspopup="true">
                        <x-avatar :user="auth()->user()" size="size-8" />
                        <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        <span class="sr-only">Menú de usuario</span>
                    </button>
                    <div x-show="menu" x-cloak x-transition.origin.top.right @click.outside="menu = false"
                         class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-gray-200 bg-white py-1 shadow-lg">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        </div>
                        <a href="{{ auth()->user()->profileUrl() }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-user w-4 text-gray-400"></i> Mi perfil público</a>
                        <a href="{{ route('library') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-book-open w-4 text-gray-400"></i> Biblioteca</a>
                        <a href="{{ route('stats.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-chart-column w-4 text-gray-400"></i> Estadísticas</a>
                        <a href="{{ route('shows.create') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-plus w-4 text-gray-400"></i> Crear programa</a>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-gear w-4 text-gray-400"></i> Ajustes</a>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50"><i class="fa-solid fa-shield-halved w-4 text-gray-400"></i> Administración</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}" class="border-t border-gray-100"
                              x-data="confirmForm({ title: '¿Cerrar sesión?', confirm: 'Sí, cerrar sesión' })" @submit="submit">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50">
                                <i class="fa-solid fa-arrow-right-from-bracket w-4"></i> Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost hidden sm:inline-flex">Iniciar sesión</a>
                <a href="{{ route('register') }}" class="btn btn-primary hidden sm:inline-flex">Crear cuenta</a>
            @endauth

            <button type="button" @click="open = ! open" class="btn-icon text-gray-600 hover:bg-gray-100 lg:hidden" :aria-expanded="open" aria-label="Abrir menú">
                <i class="fa-solid" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>
    </div>

    {{-- Menú móvil --}}
    <div x-show="open" x-cloak x-transition class="border-t border-gray-200 bg-white lg:hidden">
        <div class="space-y-1 px-4 py-3">
            <form action="{{ route('explore') }}" method="GET" class="mb-3 md:hidden" role="search">
                <input type="search" name="q" placeholder="Buscar episodios…" class="input rounded-full" aria-label="Buscar episodios">
            </form>
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}" @class([
                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium',
                    'bg-brand-50 text-brand-700' => request()->routeIs($link['active']),
                    'text-gray-700 hover:bg-gray-100' => ! request()->routeIs($link['active']),
                ])><i class="fa-solid {{ $link['icon'] }} w-5"></i> {{ $link['label'] }}</a>
            @endforeach
        </div>
        <div class="border-t border-gray-200 px-4 py-3">
            @auth
                <div class="mb-2 flex items-center gap-3 px-3">
                    <x-avatar :user="auth()->user()" size="size-9" />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <a href="{{ route('episodes.create') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold text-brand-700 hover:bg-gray-100"><i class="fa-solid fa-cloud-arrow-up w-5"></i> Subir episodio</a>
                <a href="{{ auth()->user()->profileUrl() }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-gray-100"><i class="fa-solid fa-user w-5 text-gray-400"></i> Mi perfil público</a>
                <a href="{{ route('stats.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-gray-100"><i class="fa-solid fa-chart-column w-5 text-gray-400"></i> Estadísticas</a>
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-gray-100"><i class="fa-solid fa-gear w-5 text-gray-400"></i> Ajustes</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm hover:bg-gray-100"><i class="fa-solid fa-shield-halved w-5 text-gray-400"></i> Administración</a>
                @endif
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm text-red-600 hover:bg-red-50"><i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Cerrar sesión</button>
                </form>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="btn btn-secondary">Iniciar sesión</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Crear cuenta</a>
                </div>
            @endauth
        </div>
    </div>
</nav>
