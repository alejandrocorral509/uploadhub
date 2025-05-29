<nav x-data="{ open: false, dark: localStorage.getItem('dark') === 'true' }"
     class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50 dark:bg-gray-900 dark:border-gray-700 transition-colors duration-300">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between">

            {{-- Logo y nombre a la izquierda --}}
            <div class="flex items-center space-x-4 flex-shrink-0">
                <a href="{{ route('audios.public') }}" class="flex items-center space-x-2">
                    <img src="{{ asset('images/favICON.png') }}" alt="Mi Logo" class="h-10 w-auto">
                    <span class="text-xl font-bold text-indigo-600 dark:text-indigo-400 select-none">UploadHub</span>
                </a>
            </div>

            {{-- Navegación centrada --}}
            <div class="hidden sm:flex items-center gap-8">
                <x-nav-link :href="route('audios.public')" :active="request()->routeIs('audios.public')"
                            class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400
                                transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-house text-lg"></i>
                    <span>Inicio</span>
                </x-nav-link>

                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                            class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400
                                transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-book text-lg"></i>
                    <span>Tu biblioteca</span>
                </x-nav-link>

                <x-nav-link :href="route('audios.upload')"
                            class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400
                                transition duration-150 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-lg"></i>
                    <span>Subir archivos</span>
                </x-nav-link>
            </div>
            {{-- Dark mode toggle + usuario + botón subir audio --}}
            <div class="hidden sm:flex sm:items-center sm:space-x-4 flex-shrink-0">
                {{-- Dark mode --}

                {{-- Dropdown usuario --}}
                <x-dropdown align="right" width="72">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md
                                   text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ml-2 h-5 w-5 text-gray-400 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesión
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- Hamburger (menú móvil) --}}
            <div class="sm:hidden flex items-center">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-300 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-800
                           focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition duration-150"
                    aria-label="Toggle navigation">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': !open }" class="inline-flex"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': !open, 'inline-flex': open }" class="hidden"
                              stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menú responsive móvil --}}
    <div :class="open ? 'block' : 'hidden'" class="sm:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-inner transition-colors duration-300">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('audios.public')" :active="request()->routeIs('audios.public')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                Explorar
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                Mis Archivos
            </x-responsive-nav-link>

            <x-responsive-nav-link href="#" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                Favoritos
            </x-responsive-nav-link>

            <x-responsive-nav-link href="#" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                Ajustes
            </x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4">
                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                        Cerrar sesión
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Al cargar la página, aplicar modo oscuro en <html> según localStorage
        if(localStorage.getItem('dark') === 'true') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</nav>
