<section>
    <header>
        <h2 class="text-lg font-bold">Perfil</h2>
        <p class="mt-1 text-sm text-gray-500">Tu nombre y tu biografía aparecen en tu perfil público y en tus episodios.</p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="bio" value="Biografía" />
            <textarea id="bio" name="bio" rows="3" maxlength="500" class="input" placeholder="Quién eres, de qué hablas en tus podcasts…">{{ old('bio', $user->bio) }}</textarea>
            <x-input-error :messages="$errors->get('bio')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" required autocomplete="username" />
            <p class="help">No se muestra en ningún sitio público.</p>
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="mt-2 text-sm text-gray-700">
                    Tu correo no está verificado.
                    <button form="send-verification" class="link">Reenviar el correo de verificación</button>
                </p>
                @if (session('status') === 'verification-link-sent')
                    <p class="mt-2 text-sm font-medium text-emerald-600">Te hemos enviado un nuevo enlace de verificación.</p>
                @endif
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Guardar</x-primary-button>
            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-sm text-emerald-600">
                    <i class="fa-solid fa-check"></i> Guardado
                </p>
            @endif
        </div>
    </form>
</section>
