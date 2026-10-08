@props(['type', 'id', 'reasons', 'label' => 'Denunciar'])

{{-- Denunciar un episodio, programa o comentario. Formulario en una ventana emergente. --}}
@auth
    <div x-data="{ open: false }" class="inline-block">
        <button type="button" @click="open = true" {{ $attributes->merge(['class' => 'text-xs text-gray-400 hover:text-red-600']) }}>
            <i class="fa-regular fa-flag"></i> {{ $label }}
        </button>
        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-black/40 p-4" @keydown.escape.window="open = false">
                <form method="POST" action="{{ route('reports.store') }}" @click.outside="open = false" class="card w-full max-w-md p-6">
                    @csrf
                    <input type="hidden" name="type" value="{{ $type }}">
                    <input type="hidden" name="id" value="{{ $id }}">
                    <h2 class="text-lg font-bold">¿Qué ocurre?</h2>
                    <p class="text-sm text-gray-500">El equipo de moderación revisará la denuncia.</p>
                    <fieldset class="mt-4 space-y-2">
                        <legend class="sr-only">Motivo</legend>
                        @foreach ($reasons as $value => $text)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2 text-sm has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50">
                                <input type="radio" name="reason" value="{{ $value }}" required class="accent-brand-600"> {{ $text }}
                            </label>
                        @endforeach
                    </fieldset>
                    <label for="report-details-{{ $type }}-{{ $id }}" class="label mt-4">Detalles (opcional)</label>
                    <textarea id="report-details-{{ $type }}-{{ $id }}" name="details" rows="2" maxlength="1000" class="input"></textarea>
                    <div class="mt-5 flex justify-end gap-2">
                        <button type="button" @click="open = false" class="btn btn-secondary">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Enviar denuncia</button>
                    </div>
                </form>
            </div>
        </template>
    </div>
@endauth
