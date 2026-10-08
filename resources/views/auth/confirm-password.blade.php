<x-guest-layout title="Confirmar contraseña">
    <h1 class="text-xl font-bold">Confirma tu contraseña</h1>
    <p class="mt-1 text-sm text-gray-500">Es una zona protegida: escribe tu contraseña para continuar.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>
        <x-primary-button class="w-full">Confirmar</x-primary-button>
    </form>
</x-guest-layout>
