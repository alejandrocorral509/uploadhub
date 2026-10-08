<x-guest-layout title="Verifica tu correo">
    <h1 class="text-xl font-bold">Verifica tu correo</h1>
    <p class="mt-1 text-sm text-gray-500">Te hemos enviado un enlace. Si no lo encuentras, mira en spam o pide otro.</p>

    @if (session('status') == 'verification-link-sent')
        <p class="mt-4 text-sm font-medium text-emerald-600">Te hemos enviado un enlace nuevo.</p>
    @endif

    <div class="mt-6 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>Reenviar el correo</x-primary-button>
        </form>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-900 hover:underline">Cerrar sesión</button>
        </form>
    </div>
</x-guest-layout>
