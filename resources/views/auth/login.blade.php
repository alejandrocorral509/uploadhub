<x-guest-layout title="Iniciar sesión">
    <h1 class="text-xl font-bold">Iniciar sesión</h1>
    <p class="mt-1 text-sm text-gray-500">¿No tienes cuenta? <a href="{{ route('register') }}" class="link">Créala gratis</a></p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    @if (\App\Support\Demo::enabled())
        <form method="POST" action="{{ route('demo.login') }}" class="mt-4">
            @csrf
            <button class="btn w-full bg-amber-100 text-amber-900 hover:bg-amber-200"><i class="fa-solid fa-flask"></i> Probar la demo sin registrarte</button>
        </form>
    @endif

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" value="Contraseña" />
                <a href="{{ route('password.request') }}" class="mb-1 text-xs font-medium text-brand-600 hover:underline">¿La has olvidado?</a>
            </div>
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" name="remember" class="size-4 accent-brand-600"> Recordarme en este dispositivo
        </label>

        <x-primary-button class="w-full btn-lg">
            <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar
        </x-primary-button>
    </form>
</x-guest-layout>
