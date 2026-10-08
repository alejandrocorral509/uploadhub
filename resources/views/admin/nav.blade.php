<div class="flex flex-wrap items-end justify-between gap-4">
    <h1 class="flex items-center gap-2 text-2xl font-extrabold tracking-tight"><i class="fa-solid fa-shield-halved text-brand-600"></i> Administración</h1>
</div>
<nav class="-mx-4 mt-6 flex gap-1 overflow-x-auto border-b border-gray-200 px-4" aria-label="Secciones de administración">
    @foreach ([
        'admin.dashboard' => 'Resumen',
        'admin.reports.index' => 'Denuncias',
        'admin.users.index' => 'Usuarios',
        'admin.episodes.index' => 'Episodios',
        'admin.comments.index' => 'Comentarios',
    ] as $route => $label)
        <a href="{{ route($route) }}" @class([
            '-mb-px shrink-0 border-b-2 px-4 py-2.5 text-sm font-semibold',
            'border-brand-600 text-brand-700' => request()->routeIs($route),
            'border-transparent text-gray-500 hover:text-gray-800' => ! request()->routeIs($route),
        ])>{{ $label }}</a>
    @endforeach
</nav>
