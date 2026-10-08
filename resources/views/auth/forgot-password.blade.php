<x-guest-layout title="Recuperar contraseña">
    <h1 class="text-xl font-bold">¿Has olvidado la contraseña?</h1>
    <p class="mt-1 text-sm text-gray-500">Escribe tu correo y te enviaremos un enlace para elegir una nueva.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>
        <x-primary-button class="w-full">Enviar el enlace</x-primary-button>
        <p class="text-center text-sm"><a href="{{ route('login') }}" class="link">Volver a iniciar sesión</a></p>
    </form>
</x-guest-layout>
