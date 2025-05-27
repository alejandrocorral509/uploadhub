<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;



class AudioController extends Controller
{
public function index(Request $request)
{
    $user = Auth::user();

    $query = Audio::where('user_id', $user->id)
        ->with(['categories', 'tags', 'likes', 'user']);

    // Filtrar por categoría
    if ($request->filled('category')) {
        $categoryId = $request->input('category');
        $query->whereHas('categories', function($q) use ($categoryId) {
            $q->where('categories.id', $categoryId);
        });
    }

    // Filtrar por búsqueda en título o descripción
    if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('title', 'like', "%$search%")
              ->orWhere('description', 'like', "%$search%");
        });
    }

    $audios = $query->latest()->get();

    // Audios que el usuario ha marcado como "Me gusta"
    $likedAudios = $user->likedAudios()->with(['categories', 'tags', 'user'])->latest()->get();

    // Total audios subidos
    $totalAudios = Audio::where('user_id', $user->id)->count();

    // Total likes recibidos en todos los audios del usuario
    $totalLikes = Audio::where('user_id', $user->id)
        ->withCount('likes')
        ->get()
        ->sum('likes_count');

    $categories = Category::orderBy('name')->get();

    return view('dashboard', compact('audios', 'likedAudios', 'categories', 'totalAudios', 'totalLikes'));
}





public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'audio' => 'nullable|file|mimes:mp3,wav,m4a,mp4,ogg|max:51200',
        'youtube_url' => ['nullable', 'url', 'regex:#^(https?://)?(www\.youtube\.com|youtu\.?be)/.+$#'],
        'description' => 'nullable|string',
        'privacy' => 'required|in:public,private',
        'categories' => 'required|array|min:1',  // <-- aquí la validación
        'categories.*' => 'exists:categories,id',
        'tags' => 'nullable|string',
        'cover' => 'nullable|image|max:5120', // opcional, máximo 5MB

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

    $coverPath = null;
    if ($request->hasFile('cover')) {
        $coverPath = $request->file('cover')->store('covers', 'public');
}

    // Aquí se asigna el usuario autenticado correctamente
    $audio = Audio::create([
        'user_id' => Auth::id(),
        'title' => $request->input('title'),
        'filepath' => $filepath,
        'cover' => $coverPath, // aquí guardamos la ruta
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

        if ($audio->filepath) { // cambiado de 'archivo' a 'filepath'
            Storage::disk('public')->delete($audio->filepath);
        }
        $audio->delete();

        return back()->with('success', 'Audio eliminado correctamente.');
    }

    public function publicIndex(Request $request)
    {
        $query = Audio::where('privacy', 'public')
            ->with(['categories', 'tags', 'user']);

        if ($request->filled('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
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

    public function toggleLike(Audio $audio): JsonResponse
    {
        $user = Auth::user();

        if ($audio->likes()->where('user_id', $user->id)->exists()) {
            $audio->likes()->detach($user->id);
            $liked = false;
        } else {
            $audio->likes()->attach($user->id);
            $liked = true;
        }

        $likesCount = $audio->likes()->count();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $likesCount,
        ]);
    }

public function download(Audio $audio)
{
    $userId = auth()->id();

    // Comprobar permisos: si es privado y no es propietario, bloquear acceso
    if ($audio->privacy === 'private' && $audio->user_id !== $userId) {
        abort(403, 'No tienes permiso para descargar este archivo.');
    }

    // Verificar que el archivo exista en el disco
    if (!$audio->filepath || !Storage::disk('public')->exists($audio->filepath)) {
        abort(404, 'Archivo no encontrado.');
    }

    // Descargar el archivo con nombre amigable: título + extensión original
    return Storage::disk('public')->download(
        $audio->filepath,
        $audio->title . '.' . pathinfo($audio->filepath, PATHINFO_EXTENSION)
    );
}

    
}
