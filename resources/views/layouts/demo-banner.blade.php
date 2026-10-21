{{-- Aviso de la demo pública (solo con DEMO_MODE=true) --}}
@if (\App\Support\Demo::enabled())
    <div class="bg-amber-50 text-amber-900">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-x-4 gap-y-2 px-4 py-2 text-center text-sm sm:px-6 lg:px-8">
            <p>
                <i class="fa-solid fa-flask"></i>
                <strong>Demo de portfolio.</strong> Los datos se reinician cada noche; puedes probarlo todo sin miedo.
            </p>
            @guest
                <form method="POST" action="{{ route('demo.login') }}">
                    @csrf
                    <button class="btn btn-sm bg-amber-900 text-white hover:bg-amber-800">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Entrar como creadora de ejemplo
                    </button>
                </form>
            @endguest
            @if (\App\Support\Demo::isDemoAccount(auth()->user()))
                <p class="text-amber-800">Estás en una cuenta de ejemplo compartida.</p>
            @endif
        </div>
    </div>
@endif
