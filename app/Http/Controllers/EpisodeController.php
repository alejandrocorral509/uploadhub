<?php

namespace App\Http\Controllers;

use App\Http\Requests\EpisodeRequest;
use App\Jobs\ProcessAudio;
use App\Models\Audio;
use App\Models\Category;
use App\Models\PlaybackProgress;
use App\Models\Report;
use App\Models\Tag;
use App\Services\ChunkedUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EpisodeController extends Controller
{
    public function show(Request $request, Audio $audio, ?string $slug = null): View|RedirectResponse
    {
        Gate::authorize('view', $audio);

        // URL canónica con el slug del título
        if ($slug !== $audio->slug()) {
            return redirect()->to($audio->url(), 301);
        }

        $user = $request->user();

        $audio->load(['user', 'show', 'categories', 'tags'])
            ->loadCount(['likes', 'comments']);

        $comments = $audio->comments()->with('user')->latest()->paginate(20, ['*'], 'comentarios');
        $comments->getCollection()->each->setRelation('audio', $audio);

        $moreFromShow = $audio->show
            ? $audio->show->episodes()->whereKeyNot($audio->id)->visibleTo($user)
                ->with(['user', 'show'])->latest()->limit(5)->get()
            : collect();

        $progress = $user
            ? PlaybackProgress::where('user_id', $user->id)->where('audio_id', $audio->id)->first()
            : null;

        return view('episodes.show', [
            'audio' => $audio,
            'comments' => $comments,
            'moreFromShow' => $moreFromShow,
            'liked' => $user ? $audio->likes()->whereKey($user->id)->exists() : false,
            'resumeAt' => $progress && ! $progress->completed ? $progress->position : null,
            'reportReasons' => Report::REASONS,
        ]);
    }

    public function create(Request $request): View
    {
        return view('episodes.form', [
            'audio' => new Audio(['privacy' => 'public']),
            'categories' => Category::orderBy('name')->get(),
            'shows' => $request->user()->shows()->orderBy('title')->get(),
            'selectedShow' => $request->user()->shows()->where('slug', $request->query('programa'))->value('id'),
        ]);
    }

    public function store(EpisodeRequest $request, ChunkedUpload $uploads): JsonResponse|RedirectResponse
    {
        $audio = new Audio($this->attributes($request));
        $audio->user_id = $request->user()->id;

        $this->applySource($request, $audio, $uploads);

        if ($request->hasFile('cover')) {
            $audio->cover = $request->file('cover')->store('covers', config('media.covers_disk'));
        }

        $audio->save();
        $this->syncTaxonomies($request, $audio);

        if ($audio->filepath) {
            ProcessAudio::dispatch($audio);
        }

        return $this->done($request, $audio, 'Episodio publicado.');
    }

    public function edit(Request $request, Audio $audio): View
    {
        Gate::authorize('update', $audio);

        $audio->load(['categories', 'tags']);

        return view('episodes.form', [
            'audio' => $audio,
            'categories' => Category::orderBy('name')->get(),
            'shows' => $request->user()->shows()->orderBy('title')->get(),
            'selectedShow' => $audio->show_id,
        ]);
    }

    public function update(EpisodeRequest $request, Audio $audio, ChunkedUpload $uploads): JsonResponse|RedirectResponse
    {
        $audio->fill($this->attributes($request));

        $sourceChanged = $request->hasFile('audio') || $request->filled('upload_token') || $request->filled('youtube_url');
        if ($sourceChanged) {
            $old = clone $audio;
            $this->applySource($request, $audio, $uploads);
            if ($old->filepath && $old->filepath !== $audio->filepath) {
                $old->deleteFile();
            }
        }

        if ($request->hasFile('cover') || $request->boolean('remove_cover')) {
            $audio->deleteCover();
            $audio->cover = $request->hasFile('cover')
                ? $request->file('cover')->store('covers', config('media.covers_disk'))
                : null;
        }

        $audio->save();
        $this->syncTaxonomies($request, $audio);

        if ($sourceChanged && $audio->filepath) {
            ProcessAudio::dispatch($audio);
        }

        return $this->done($request, $audio, 'Episodio actualizado.');
    }

    public function destroy(Request $request, Audio $audio): RedirectResponse
    {
        Gate::authorize('delete', $audio);

        $isOwner = $request->user()->id === $audio->user_id;
        $audio->delete();

        // Un administrador que modera vuelve a donde estaba; el autor, a su biblioteca
        return ($isOwner ? redirect()->route('library') : back())->with('success', 'Episodio eliminado.');
    }

    // Sirve el archivo al reproductor. Admite peticiones Range para poder saltar a cualquier punto.
    public function stream(Audio $audio): Response
    {
        Gate::authorize('view', $audio);

        $diskName = $audio->fileDisk();
        abort_unless($diskName, 404, 'Archivo no encontrado.');
        $disk = Storage::disk($diskName);

        // En S3 o R2 se redirige a una URL firmada de corta duración
        if (config("filesystems.disks.{$diskName}.driver") === 's3') {
            return redirect()->away($disk->temporaryUrl($audio->filepath, now()->addMinutes(30)));
        }

        // El tipo se deduce del propio archivo, nunca de lo que dijo el navegador al subirlo,
        // y nosniff impide que el navegador lo interprete como HTML
        $mime = $disk->mimeType($audio->filepath) ?: 'application/octet-stream';
        if (! Str::startsWith($mime, ['audio/', 'video/'])) {
            $mime = 'application/octet-stream';
        }

        return response()->file($disk->path($audio->filepath), [
            'Content-Type' => $mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $audio->isPublic() ? 'public, max-age=86400' : 'private, no-store',
        ]);
    }

    public function download(Audio $audio): Response
    {
        Gate::authorize('view', $audio);

        $diskName = $audio->fileDisk();
        abort_unless($diskName, 404, 'Este episodio no tiene archivo para descargar.');

        $extension = strtolower(pathinfo($audio->filepath, PATHINFO_EXTENSION));

        return Storage::disk($diskName)->download($audio->filepath, $audio->slug().'.'.$extension);
    }

    private function attributes(EpisodeRequest $request): array
    {
        return $request->safe()->only(['title', 'description', 'show_id', 'season', 'episode_number', 'privacy']);
    }

    private function applySource(EpisodeRequest $request, Audio $audio, ChunkedUpload $uploads): void
    {
        if ($request->hasFile('audio')) {
            $file = $request->file('audio');
            $mime = $file->getMimeType() ?: 'application/octet-stream';
            abort_unless(ChunkedUpload::isAllowedMime($mime), 422, 'El archivo no es un audio válido.');

            $audio->filepath = $file->store('audios', config('media.disk'));
            $audio->filetype = $mime;
            $audio->youtube_url = null;
            $audio->processing_status = Audio::STATUS_PENDING;
            $audio->duration = null;
            $audio->waveform = null;
        } elseif ($request->filled('upload_token')) {
            $file = $uploads->claim($request->input('upload_token'), $request->user());
            $audio->filepath = $file['path'];
            $audio->filetype = $file['mime'];
            $audio->youtube_url = null;
            $audio->processing_status = Audio::STATUS_PENDING;
            $audio->duration = null;
            $audio->waveform = null;
        } elseif ($request->filled('youtube_url')) {
            $audio->filepath = null;
            $audio->filetype = 'youtube';
            $audio->youtube_url = $request->input('youtube_url');
            $audio->processing_status = Audio::STATUS_READY;
            $audio->duration = null;
            $audio->waveform = null;
        }
    }

    private function syncTaxonomies(EpisodeRequest $request, Audio $audio): void
    {
        $audio->categories()->sync($request->input('categories', []));

        $tagIds = collect($request->tagNames())
            ->map(fn (string $name) => Tag::firstOrCreate(['name' => $name])->id);

        $audio->tags()->sync($tagIds);
    }

    // El formulario de subida envía por fetch y espera JSON; un formulario normal, una redirección
    private function done(Request $request, Audio $audio, string $message): JsonResponse|RedirectResponse
    {
        session()->flash('success', $message);

        if ($request->wantsJson()) {
            return response()->json(['redirect' => $audio->url()]);
        }

        return redirect()->to($audio->url());
    }
}
