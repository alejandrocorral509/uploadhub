<section class="space-y-4">
    <header>
        <h2 class="text-lg font-bold text-red-700">Eliminar la cuenta</h2>
        <p class="mt-1 text-sm text-gray-500">Se borrarán para siempre tu perfil, tus programas, tus episodios (con sus archivos), tus comentarios y tus estadísticas.</p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">Eliminar mi cuenta</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold">¿Seguro que quieres eliminar tu cuenta?</h2>
            <p class="mt-1 text-sm text-gray-500">No se puede deshacer. Escribe tu contraseña para confirmarlo.</p>

            <div class="mt-6">
                <x-input-label for="password" value="Contraseña" class="sr-only" />
                <x-text-input id="password" name="password" type="password" placeholder="Contraseña" />
                <x-input-error :messages="$errors->userDeletion->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button>Eliminar definitivamente</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
