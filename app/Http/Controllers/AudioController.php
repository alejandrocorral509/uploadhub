<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Tag;

class AudioController extends Controller
{
    public function index()
    {
        $audios = Audio::where('user_id', Auth::id())
            ->with(['categories', 'tags'])
            ->latest()
            ->get();

        $categories = Category::orderBy('name')->get();

        return view('dashboard', compact('audios', 'categories'));
    }

public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'audio' => 'nullable|file|mimes:mp3,wav,m4a,mp4,ogg|max:51200',
        'youtube_url' => ['nullable', 'url', 'regex:#^(https?://)?(www\.youtube\.com|youtu\.?be)/.+$#'],
        'description' => 'nullable|string',
        'privacy' => 'required|in:public,private',
        'categories' => 'nullable|array',
        'categories.*' => 'exists:categories,id',
        'tags' => 'nullable|string',
    ]);

    if (!$request->hasFile('audio') && !$request->filled('youtube_url')) {
        return back()->withErrors(['audio' => 'Debe subir un archivo o proporcionar un enlace de YouTube'])->withInput();
    }

    $filepath = null;
    $filetype = null;

    if ($request->hasFile('audio')) {
        $file = $request->file('audio');
        $filepath = $file->store('audios', 'public');
        $filetype = $file->getClientMimeType();
    }

    $youtubeUrl = $request->input('youtube_url', null);

    $audio = Audio::create([
        'user_id' => Auth::id(),
        'titulo' => $request->input('title'),
        'archivo' => $filepath,
        'youtube_url' => $youtubeUrl,
        'description' => $request->input('description'),
        'filetype' => $filepath ? $filetype : ($youtubeUrl ? 'youtube' : null),
        'privacy' => $request->input('privacy'),
    ]);

    if ($request->has('categories')) {
        $audio->categories()->sync($request->input('categories'));
    }

    if ($request->has('tags')) {
        $tags = collect(explode(',', $request->input('tags')))
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->unique();

        $tagIds = $tags->map(fn($tagName) => Tag::firstOrCreate(['name' => $tagName])->id);

        $audio->tags()->sync($tagIds);
    }

    return redirect()->route('dashboard')->with('success', 'Audio subido correctamente.');
}


    public function destroy(Audio $audio)
    {
        if ($audio->user_id !== Auth::id()) {
            abort(403);
        }

        if ($audio->archivo) {
            Storage::disk('public')->delete($audio->archivo);
        }
        $audio->delete();

        return back()->with('success', 'Audio eliminado correctamente.');
    }

    public function publicIndex(Request $request)
    {
        $query = Audio::where('privacy', 'public')
            ->with(['categories', 'tags', 'user']);

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('titulo', 'like', "%$search%")
                    ->orWhere('description', 'like', "%$search%");
            });
        }

        $audios = $query->latest()->paginate(12);

        $categories = Category::orderBy('name')->get();

        return view('audios.public', compact('audios', 'categories'));
    }

public function upload()
{
    $categories = Category::all();

    return view('audios.upload', compact('categories'));
}

}
