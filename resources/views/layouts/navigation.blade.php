<nav x-data="{ open: false, dark: localStorage.getItem('dark') === 'true' }" 
     :class="{ 'dark': dark }" 
     class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50 dark:bg-gray-900 dark:border-gray-700 transition-colors duration-300">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <div class="flex items-center space-x-6">
                <!-- Logo -->
                <div class="flex-shrink-0">
                    <a href="{{ route('dashboard') }}">
                        <x-application-logo class="block h-10 w-auto fill-current text-indigo-600 dark:text-indigo-400" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden sm:flex space-x-8">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                        class="text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400 transition duration-150">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <!-- Más enlaces si deseas -->
                </div>
            </div>

            <!-- Settings Dropdown + Dark Mode Toggle -->
            <div class="hidden sm:flex sm:items-center sm:space-x-4">

                <!-- Dark Mode Toggle -->
                <button
                    @click="dark = !dark; localStorage.setItem('dark', dark); document.documentElement.classList.toggle('dark', dark)"
                    class="p-2 rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition"
                    :aria-label="dark ? 'Switch to light mode' : 'Switch to dark mode'"
                    title="Alternar modo oscuro"
                >
                    <template x-if="!dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m8.485-8.485l-.707.707M4.222 4.222l-.707.707M21 12h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707" />
                            <circle cx="12" cy="12" r="5" stroke="none" fill="currentColor"/>
                        </svg>
                    </template>
                    <template x-if="dark">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24" stroke="none">
                            <path d="M21 12.79A9 9 0 1111.21 3a7 7 0 009.79 9.79z"/>
                        </svg>
                    </template>
                </button>

                <!-- User Dropdown -->
                <x-dropdown align="right" width="56">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md
                                   text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-100 dark:hover:bg-gray-700
                                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150"
                        >
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="ml-2 h-5 w-5 text-gray-400 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger (Mobile menu button) -->
            <div class="sm:hidden flex items-center">
                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 dark:text-gray-300 hover:text-indigo-600 hover:bg-gray-100 dark:hover:bg-gray-800
                           focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500 transition duration-150"
                    aria-label="Toggle navigation"
                >
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

    <!-- Responsive Navigation Menu -->
    <div :class="open ? 'block' : 'hidden'" class="sm:hidden border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 shadow-inner transition-colors duration-300">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')"
                class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-700">
            <div class="px-4">
                <div class="font-semibold text-gray-900 dark:text-gray-200">{{ Auth::user()->name }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')" class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="block px-4 py-2 text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-700 hover:text-indigo-700 dark:hover:text-indigo-200 transition"
                    >
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Aplica el modo oscuro al cargar según localStorage
        if(localStorage.getItem('dark') === 'true') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</nav>
