<x-app-layout title="Denuncias">
    <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
        @include('admin.nav')

        <div class="mt-6 inline-flex rounded-lg bg-gray-100 p-1 text-sm font-medium">
            @foreach (['open' => 'Abiertas', 'resolved' => 'Resueltas', 'dismissed' => 'Descartadas'] as $value => $label)
                <a href="{{ route('admin.reports.index', ['estado' => $value]) }}" @class([
                    'rounded-md px-3 py-1.5',
                    'bg-white text-gray-900 shadow-sm' => $status === $value,
                    'text-gray-500' => $status !== $value,
                ])>{{ $label }}</a>
            @endforeach
        </div>

        <ul class="card mt-4 divide-y divide-gray-100">
            @forelse ($reports as $report)
                @include('admin.report-item')
            @empty
                <li class="p-6 text-center text-sm text-gray-500">No hay denuncias en este estado.</li>
            @endforelse
        </ul>
        <div class="mt-6">{{ $reports->links() }}</div>
    </div>
</x-app-layout>
