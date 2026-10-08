@props(['episode'])

@if ($episode->filepath)
    @php($payload = $episode->playerPayload())
    <button type="button" x-data @click="$store.player.enqueue(@js($payload))"
            {{ $attributes->merge(['class' => 'btn-icon size-9 text-gray-500 hover:bg-gray-100 hover:text-brand-700']) }}
            :class="$store.player.inQueue({{ $episode->id }}) && 'text-brand-600'"
            title="Añadir a la cola" aria-label="Añadir a la cola">
        <i class="fa-solid fa-list-ul"></i>
    </button>
@endif
