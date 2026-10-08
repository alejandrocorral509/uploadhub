{{-- Una denuncia con lo denunciado y las acciones --}}
@php($item = $report->reportable)
<li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start">
    <div class="min-w-0 flex-1">
        <p class="text-xs text-gray-500">
            <span class="badge badge-red">{{ $report->reasonLabel() }}</span>
            · {{ ['audio' => 'Episodio', 'show' => 'Programa', 'comment' => 'Comentario'][$report->reportable_type] ?? $report->reportable_type }}
            · denunciado por {{ $report->reporter->name }} {{ $report->created_at->diffForHumans() }}
        </p>
        @if (! $item)
            <p class="mt-1 text-sm text-gray-400 italic">El contenido ya no existe.</p>
        @elseif ($item instanceof \App\Models\Comment)
            <p class="mt-1 text-sm">«{{ \Illuminate\Support\Str::limit($item->content, 200) }}»</p>
            <p class="text-xs text-gray-500">de {{ $item->user->name }} en <a href="{{ $item->audio->url() }}#comentarios" class="link">{{ $item->audio->title }}</a></p>
        @elseif ($item instanceof \App\Models\Audio)
            <a href="{{ $item->url() }}" class="mt-1 block font-semibold hover:text-brand-700">{{ $item->title }}</a>
            <p class="text-xs text-gray-500">de {{ $item->user->name }}</p>
        @else
            <a href="{{ route('shows.show', $item) }}" class="mt-1 block font-semibold hover:text-brand-700">{{ $item->title }}</a>
            <p class="text-xs text-gray-500">de {{ $item->user->name }}</p>
        @endif
        @if ($report->details)
            <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">{{ $report->details }}</p>
        @endif
        @if ($report->resolver)
            <p class="mt-2 text-xs text-gray-400">Revisada por {{ $report->resolver->name }} {{ $report->resolved_at?->diffForHumans() }}</p>
        @endif
    </div>
    @if ($report->status === 'open')
        <div class="flex shrink-0 gap-2">
            <form method="POST" action="{{ route('admin.reports.update', $report) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="action" value="dismiss">
                <button class="btn btn-secondary btn-sm">Descartar</button>
            </form>
            @if ($item)
                <form method="POST" action="{{ route('admin.reports.update', $report) }}"
                      x-data="confirmForm({ title: '¿Eliminar el contenido?', text: 'Se borrará para siempre y se cerrarán sus denuncias.', confirm: 'Eliminar', danger: true })" @submit="submit">
                    @csrf @method('PATCH')
                    <input type="hidden" name="action" value="remove">
                    <button class="btn btn-danger btn-sm">Eliminar contenido</button>
                </form>
            @endif
        </div>
    @endif
</li>
