@props(['user', 'size' => 'size-10'])

@php
    // Color estable por usuario a partir de su id
    $palette = ['from-brand-500 to-fuchsia-500', 'from-sky-500 to-brand-500', 'from-emerald-500 to-teal-500', 'from-amber-500 to-rose-500', 'from-violet-500 to-pink-500'];
    $gradient = $palette[$user->id % count($palette)];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex shrink-0 items-center justify-center rounded-full bg-gradient-to-br {$gradient} {$size} text-xs font-bold text-white"]) }}
      aria-hidden="true">{{ $user->initials() }}</span>
