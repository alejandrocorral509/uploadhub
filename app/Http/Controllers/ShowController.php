<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowRequest;
use App\Models\Category;
use App\Models\PlaybackProgress;
use App\Models\Report;
use App\Models\Show;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ShowController extends Controller
{
    public const LANGUAGES = [
        'es' => 'Español', 'en' => 'Inglés', 'ca' => 'Catalán', 'gl' => 'Gallego', 'eu' => 'Euskera',
        'pt' => 'Portugués', 'fr' => 'Francés', 'it' => 'Italiano', 'de' => 'Alemán',
    ];

    public function index(Request $request): View
    {
        $shows = Show::with(['user', 'category'])
            ->withCount(['episodes' => fn ($q) => $q->public(), 'subscribers'])
            ->whereHas('episodes', fn ($q) => $q->public())
            ->when($request->query('q'), fn ($q, $term) => $q->where('title', 'like', '%'.$term.'%'))
            ->when($request->integer('categoria'), fn ($q, $id) => $q->where('category_id', $id))
            ->orderByDesc('subscribers_count')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('shows.index', [
            'shows' => $shows,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Show $show): View
    {
        $user = $request->user();

        $show->load(['user', 'category'])->loadCount('subscribers');

        $episodes = $show->episodes()
            ->visibleTo($user)
            ->with(['user', 'show'])
            ->withCount('likes')
            ->orderByDesc('season')
            ->orderByDesc('episode_number')
            ->latest()
            ->paginate(20);

        return view('shows.show', [
            'show' => $show,
            'episodes' => $episodes,
            'subscribed' => $user ? $show->subscribers()->whereKey($user->id)->exists() : false,
            'positions' => PlaybackProgress::positionsFor($user, $episodes),
            'reportReasons' => Report::REASONS,
        ]);
    }

    public function create(): View
    {
        return view('shows.form', [
            'show' => new Show(['language' => 'es']),
            'categories' => Category::orderBy('name')->get(),
            'languages' => self::LANGUAGES,
        ]);
    }

    public function store(ShowRequest $request): RedirectResponse
    {
        $show = new Show($request->safe()->except(['cover', 'remove_cover', 'explicit']));
        $show->explicit = $request->boolean('explicit');
        $show->user_id = $request->user()->id;

        if ($request->hasFile('cover')) {
            $show->cover = $request->file('cover')->store('covers', config('media.covers_disk'));
        }

        $show->save();

        return redirect()->route('shows.show', $show)->with('success', 'Programa creado. Ya puedes subir episodios.');
    }

    public function edit(Show $show): View
    {
        Gate::authorize('update', $show);

        return view('shows.form', [
            'show' => $show,
            'categories' => Category::orderBy('name')->get(),
            'languages' => self::LANGUAGES,
        ]);
    }

    public function update(ShowRequest $request, Show $show): RedirectResponse
    {
        $show->fill($request->safe()->except(['cover', 'remove_cover', 'explicit']));
        $show->explicit = $request->boolean('explicit');

        if ($show->isDirty('title')) {
            $show->slug = Show::uniqueSlug($show->title, $show->id);
        }

        if ($request->hasFile('cover') || $request->boolean('remove_cover')) {
            if ($show->cover) {
                Storage::disk(config('media.covers_disk'))->delete($show->cover);
            }
            $show->cover = $request->hasFile('cover')
                ? $request->file('cover')->store('covers', config('media.covers_disk'))
                : null;
        }

        $show->save();

        return redirect()->route('shows.show', $show)->with('success', 'Programa actualizado.');
    }

    // Los episodios no se borran: se quedan como episodios sueltos
    public function destroy(Show $show): RedirectResponse
    {
        Gate::authorize('delete', $show);

        $show->delete();

        return redirect()->route('library', ['tab' => 'programas'])->with('success', 'Programa eliminado. Sus episodios siguen publicados como episodios sueltos.');
    }
}
