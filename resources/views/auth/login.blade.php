<x-guest-layout>
    <!-- Logo -->
    <div class="flex justify-center mb-4">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-32 w-auto">
    </div>
    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email -->
        <div>
            <x-input-label for="email">
                Correo electrónico <span class="text-red-600">*</span>
            </x-input-label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <x-text-input id="email" 
                              class="block mt-1 w-full pl-10 py-2.5 border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                              type="email" 
                              name="email" 
                              :value="old('email')" 
                              required 
                              autofocus 
                              autocomplete="username" />
            </div>
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password">
                Contraseña <span class="text-red-600">*</span>
            </x-input-label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <x-text-input id="password" 
                              class="block mt-1 w-full pl-10 py-2.5 border-gray-300 focus:border-gray-500 focus:ring-gray-500"
                              type="password" 
                              name="password" 
                              required 
                              autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('register') }}">
                ¿No tienes una cuenta?
            </a>
            <x-primary-button>
                <i class="fa-solid fa-arrow-right-to-bracket mr-2"></i> Entrar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
