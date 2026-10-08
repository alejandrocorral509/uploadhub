<x-guest-layout title="Crear cuenta">
    <h1 class="text-xl font-bold">Crear cuenta</h1>
    <p class="mt-1 text-sm text-gray-500">¿Ya tienes una? <a href="{{ route('login') }}" class="link">Inicia sesión</a></p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <p class="help">Es el nombre que verán quienes escuchen tus episodios.</p>
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="new-password" />
            <p class="help">Al menos 8 caracteres, con letras y números.</p>
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Repite la contraseña" />
            <x-text-input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <x-primary-button class="w-full btn-lg">
            <i class="fa-solid fa-user-plus"></i> Crear cuenta
        </x-primary-button>
    </form>
</x-guest-layout>
