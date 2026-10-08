<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">

    @php($pageTitle = $title ? $title.' · '.config('app.name') : config('app.name').' · Podcasts para escuchar y compartir')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $description ?? 'Sube, escucha y comparte podcasts. Programas con feed RSS, reproductor que no se corta y estadísticas para creadores.' }}">
    <meta property="og:title" content="{{ $title ?? config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? 'Plataforma de podcasts' }}">
    <meta property="og:type" content="website">
    @if ($image)
        <meta property="og:image" content="{{ $image }}">
    @endif
    {{ $head ?? '' }}

    <link rel="icon" href="{{ asset('images/favICON.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col" data-user="{{ auth()->id() }}">
    @include('layouts.navigation')

    @if (session('success'))
        <div x-data="flash(@js(session('success')))"></div>
    @endif
    @if (session('error'))
        <div x-data="flash(@js(session('error')), 'error')"></div>
    @endif

    <main class="flex-1">
        {{ $slot }}
    </main>

    @include('layouts.footer')

    {{-- Hueco para que el reproductor no tape el final de la página --}}
    <div x-data x-show="$store.player.current" x-cloak class="h-28 sm:h-24"></div>

    @include('layouts.player')
</body>
</html>
