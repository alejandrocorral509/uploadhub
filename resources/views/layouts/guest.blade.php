<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4f46e5">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/favICON.png') }}" type="image/png">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body data-user="{{ auth()->id() }}" class="flex min-h-screen flex-col items-center justify-center bg-gradient-to-br from-brand-50 via-white to-fuchsia-50 px-4 py-10">
    @if (session('success'))
        <div x-data="flash(@js(session('success')))"></div>
    @endif

    <a href="{{ route('home') }}" class="mb-6 flex items-center gap-2">
        <img src="{{ asset('images/favICON.png') }}" alt="" class="h-11 w-auto">
        <span class="text-2xl font-extrabold tracking-tight text-brand-700">UploadHub</span>
    </a>

    <main class="card w-full max-w-md p-6 sm:p-8">
        {{ $slot }}
    </main>

    <a href="{{ route('home') }}" class="mt-6 text-sm text-gray-500 hover:text-brand-700"><i class="fa-solid fa-arrow-left"></i> Volver a la portada</a>

    {{-- El reproductor sigue disponible si se llega aquí con algo sonando --}}
    @include('layouts.player')
</body>
</html>
