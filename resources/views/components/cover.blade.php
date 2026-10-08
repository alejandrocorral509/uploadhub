@props(['url' => null, 'alt' => '', 'icon' => 'fa-microphone', 'seed' => 0])

@php
    $palette = ['from-brand-500 to-fuchsia-500', 'from-sky-500 to-brand-600', 'from-emerald-500 to-teal-600', 'from-amber-500 to-rose-500', 'from-violet-600 to-pink-500', 'from-slate-700 to-brand-600'];
    $gradient = $palette[abs((int) $seed) % count($palette)];
@endphp

@if ($url)
    <img src="{{ $url }}" alt="{{ $alt }}" loading="lazy" {{ $attributes->merge(['class' => 'object-cover bg-gray-100']) }}>
@else
    <div {{ $attributes->merge(['class' => "flex items-center justify-center bg-gradient-to-br {$gradient} text-white/90"]) }} role="img" aria-label="{{ $alt }}">
        <i class="fa-solid {{ $icon }} text-[length:inherit]"></i>
    </div>
@endif
