<x-app-layout title="Administración">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.nav')

        <div class="mt-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <x-stat-tile label="Usuarios" :value="number_format($counts['users'], 0, ',', '.')" :hint="$counts['banned'] ? $counts['banned'].' bloqueados' : null" />
            <x-stat-tile label="Episodios" :value="number_format($counts['episodes'], 0, ',', '.')" :hint="$counts['failed'] ? $counts['failed'].' con error al procesar' : null" />
            <x-stat-tile label="Programas" :value="number_format($counts['shows'], 0, ',', '.')" />
            <x-stat-tile label="Escuchas (7 días)" :value="number_format($counts['plays_week'], 0, ',', '.')" />
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-3">
            <section class="card lg:col-span-2">
                <div class="flex items-center justify-between px-5 pt-5">
                    <h2 class="font-bold">Denuncias abiertas <span class="badge {{ $counts['open_reports'] ? 'badge-red' : 'badge-gray' }}">{{ $counts['open_reports'] }}</span></h2>
                    <a href="{{ route('admin.reports.index') }}" class="link text-sm">Ver todas</a>
                </div>
                <ul class="mt-2 divide-y divide-gray-100">
                    @forelse ($reports as $report)
                        @include('admin.report-item')
                    @empty
                        <li class="p-5 text-sm text-gray-500"><i class="fa-solid fa-check text-emerald-500"></i> No hay denuncias pendientes.</li>
                    @endforelse
                </ul>
            </section>

            <section class="card">
                <h2 class="px-5 pt-5 font-bold">Últimos registros</h2>
                <ul class="mt-2 divide-y divide-gray-100">
                    @foreach ($newUsers as $user)
                        <li class="flex items-center gap-3 px-5 py-3">
                            <x-avatar :user="$user" size="size-8" />
                            <div class="min-w-0">
                                <a href="{{ $user->profileUrl() }}" class="block truncate text-sm font-semibold hover:text-brand-700">{{ $user->name }}</a>
                                <p class="text-xs text-gray-500">{{ $user->created_at->diffForHumans() }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
</x-app-layout>
