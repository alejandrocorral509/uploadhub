@props(['episode', 'resumeAt' => null, 'size' => 'size-11', 'label' => false])

{{-- Botón para reproducir en el reproductor global. Los episodios de YouTube se abren en su página. --}}
@if ($episode->isYoutube())
    <a href="{{ $episode->url() }}" {{ $attributes->merge(['class' => "btn-icon {$size} shrink-0 bg-red-600 text-white shadow-sm hover:bg-red-700"]) }}
       title="Ver en la página del episodio" aria-label="Ver vídeo de {{ $episode->title }}">
        <i class="fa-brands fa-youtube"></i>
    </a>
@elseif ($episode->filepath)
    @php($payload = $episode->playerPayload($resumeAt))
    <button type="button" x-data @click="$store.player.play(@js($payload))"
            {{ $attributes->merge(['class' => ($label ? 'btn btn-primary btn-lg' : "btn-icon {$size} shrink-0 bg-brand-600 text-white shadow-sm hover:bg-brand-700")]) }}
            aria-label="Reproducir o pausar «{{ $episode->title }}»">
        <i class="fa-solid" :class="$store.player.isPlaying({{ $episode->id }}) ? 'fa-pause' : 'fa-play {{ $label ? '' : 'ml-0.5' }}'"></i>
        @if ($label)
            <span x-text="$store.player.isPlaying({{ $episode->id }}) ? 'Pausar' : '{{ $resumeAt ? 'Continuar' : 'Reproducir' }}'"></span>
        @endif
    </button>
@endif
