@props(['icon' => 'fa-podcast', 'title', 'text' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border-2 border-dashed border-gray-200 px-6 py-12 text-center']) }}>
    <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-brand-50 text-xl text-brand-600">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <h3 class="mt-4 font-semibold text-gray-900">{{ $title }}</h3>
    @if ($text)
        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
