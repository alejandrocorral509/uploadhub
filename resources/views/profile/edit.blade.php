<x-app-layout title="Ajustes">
    <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">
        <div>
            <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl">Ajustes de la cuenta</h1>
            <p class="mt-1 text-sm text-gray-500">Tu perfil público: <a href="{{ $user->profileUrl() }}" class="link">{{ $user->profileUrl() }}</a></p>
        </div>

        <div class="card p-6">@include('profile.partials.update-profile-information-form')</div>
        <div class="card p-6">@include('profile.partials.update-password-form')</div>
        <div class="card border-red-200 p-6">@include('profile.partials.delete-user-form')</div>
    </div>
</x-app-layout>
