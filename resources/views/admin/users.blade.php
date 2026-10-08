<x-app-layout title="Usuarios">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.nav')

        <form method="GET" class="mt-6 flex flex-wrap gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Nombre o correo…" class="input max-w-sm" aria-label="Buscar usuarios">
            <select name="filtro" class="input w-auto" onchange="this.form.requestSubmit()" aria-label="Filtro">
                <option value="">Todos</option>
                <option value="bloqueados" @selected(request('filtro') === 'bloqueados')>Bloqueados</option>
            </select>
            <button class="btn btn-secondary">Buscar</button>
        </form>

        <div class="card mt-4 overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="border-b border-gray-200 bg-gray-50 text-left text-xs font-semibold tracking-wide text-gray-500 uppercase">
                    <tr>
                        <th scope="col" class="px-4 py-3">Usuario</th>
                        <th scope="col" class="px-4 py-3 text-right">Episodios</th>
                        <th scope="col" class="px-4 py-3 text-right">Comentarios</th>
                        <th scope="col" class="px-4 py-3">Alta</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        <tr @class(['bg-red-50/40' => $user->isBanned()])>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-avatar :user="$user" size="size-8" />
                                    <div class="min-w-0">
                                        <a href="{{ $user->profileUrl() }}" class="font-semibold hover:text-brand-700">{{ $user->name }}</a>
                                        @if ($user->isAdmin()) <span class="badge badge-brand">Admin</span> @endif
                                        @if ($user->isBanned()) <span class="badge badge-red">Bloqueado</span> @endif
                                        <p class="truncate text-xs text-gray-500">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $user->audios_count }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ $user->comments_count }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                            <td class="px-4 py-3">
                                @unless ($user->is(auth()->user()))
                                    <div class="flex justify-end gap-2">
                                        <form method="POST" action="{{ route('admin.users.admin', $user) }}"
                                              x-data="confirmForm({ title: @js($user->isAdmin() ? '¿Quitar el rol de administrador?' : '¿Hacer administrador a '.$user->name.'?') })" @submit="submit">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-ghost btn-sm">{{ $user->isAdmin() ? 'Quitar admin' : 'Hacer admin' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.ban', $user) }}"
                                              x-data="confirmForm({ title: @js($user->isBanned() ? '¿Desbloquear a '.$user->name.'?' : '¿Bloquear a '.$user->name.'?'), text: @js($user->isBanned() ? '' : 'No podrá iniciar sesión y se cerrarán sus sesiones abiertas.'), danger: {{ $user->isBanned() ? 'false' : 'true' }} })" @submit="submit">
                                            @csrf @method('PATCH')
                                            <button class="btn btn-sm {{ $user->isBanned() ? 'btn-secondary' : 'btn-danger' }}">{{ $user->isBanned() ? 'Desbloquear' : 'Bloquear' }}</button>
                                        </form>
                                    </div>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </div>
</x-app-layout>
