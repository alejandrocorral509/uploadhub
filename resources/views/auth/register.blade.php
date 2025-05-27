<x-guest-layout>
    <!-- Logo -->
    <div class="flex justify-center mb-4">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-32 w-auto">
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name">
                Nombre <span class="text-red-600">*</span>
            </x-input-label>
            <div class="relative">
                <i class="fa-solid fa-user absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <x-text-input id="name"
                              class="block mt-1 w-full pl-10 py-2.5"
                              type="text"
                              name="name"
                              :value="old('name')"
                              required autofocus
                              autocomplete="name" />
            </div>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email -->
        <div class="mt-4">
            <x-input-label for="email">
                Correo electrónico <span class="text-red-600">*</span>
            </x-input-label>
            <div class="relative">
                <i class="fa-solid fa-envelope absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <x-text-input id="email"
                              class="block mt-1 w-full pl-10 py-2.5"
                              type="email"
                              name="email"
                              :value="old('email')"
                              required
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
                              class="block mt-1 w-full pl-10 py-2.5"
                              type="password"
                              name="password"
                              required
                              autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation">
                Confirmar contraseña <span class="text-red-600">*</span>
            </x-input-label>
            <div class="relative">
                <i class="fa-solid fa-lock absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                <x-text-input id="password_confirmation"
                              class="block mt-1 w-full pl-10 py-2.5"
                              type="password"
                              name="password_confirmation"
                              required
                              autocomplete="new-password" />
            </div>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-indigo-600 hover:underline" href="{{ route('login') }}">
                ¿Ya tienes cuenta?
            </a>
            <x-primary-button>
                <i class="fa-solid fa-user-plus mr-2"></i> Registrarse
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
