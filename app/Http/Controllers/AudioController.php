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

        $allowedSorts = ['title', 'created_at', 'user_name', 'category_name', 'tag_name'];

        // Parámetros tabla audios subidos
        $sortUploaded = $request->input('sort_uploaded', 'created_at');
        $directionUploaded = strtolower($request->input('direction_uploaded', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($sortUploaded, $allowedSorts)) {
            $sortUploaded = 'created_at';
        }

        $categoryUploaded = $request->input('category_uploaded');
        $searchUploaded = $request->input('search_uploaded');

        // Parámetros tabla audios favoritos
        $sortLiked = $request->input('sort_liked', 'created_at');
        $directionLiked = strtolower($request->input('direction_liked', 'desc')) === 'asc' ? 'asc' : 'desc';
        if (!in_array($sortLiked, $allowedSorts)) {
            $sortLiked = 'created_at';
        }

        $categoryLiked = $request->input('category_liked');
        $searchLiked = $request->input('search_liked');

        // Query audios subidos
        $query = Audio::where('user_id', $user->id)
            ->with(['categories', 'tags', 'likes', 'user']);

        // Aplicar filtros subidos
        if ($categoryUploaded) {
            $query->whereHas('categories', function ($q) use ($categoryUploaded) {
                $q->where('categories.id', $categoryUploaded);
            });
        }
        if ($searchUploaded) {
            $query->where(function ($q) use ($searchUploaded) {
                $q->where('title', 'like', "%$searchUploaded%")
                  ->orWhere('description', 'like', "%$searchUploaded%");
            });
        }

        // Ordenación subidos
        switch ($sortUploaded) {
            case 'user_name':
                $query->join('users', 'audios.user_id', '=', 'users.id')
                      ->orderBy('users.name', $directionUploaded)
                      ->select('audios.*');
                break;

            case 'category_name':
                $query->orderBy(
                    Category::select('name')
                        ->join('audio_category', 'categories.id', '=', 'audio_category.category_id')
                        ->whereColumn('audio_category.audio_id', 'audios.id')
                        ->limit(1),
                    $directionUploaded
                );
                break;

            case 'tag_name':
                $query->orderBy(
                    Tag::select('name')
                        ->join('audio_tag', 'tags.id', '=', 'audio_tag.tag_id')
                        ->whereColumn('audio_tag.audio_id', 'audios.id')
                        ->limit(1),
                    $directionUploaded
                );
                break;

            default:
                $query->orderBy($sortUploaded, $directionUploaded);
        }

        // PAGINACIÓN audios subidos (cambiado nombre parámetro página)
        $audios = $query->distinct()->paginate(5, ['*'], 'page_subidos')->withQueryString();

        // Query audios favoritos
        $likedQuery = $user->likedAudios()->with(['categories', 'tags', 'user']);

        // Aplicar filtros favoritos
        if ($categoryLiked) {
            $likedQuery->whereHas('categories', function ($q) use ($categoryLiked) {
                $q->where('categories.id', $categoryLiked);
            });
        }
        if ($searchLiked) {
            $likedQuery->where(function ($q) use ($searchLiked) {
                $q->where('title', 'like', "%$searchLiked%")
                  ->orWhere('description', 'like', "%$searchLiked%");
            });
        }

        // Ordenación favoritos
        switch ($sortLiked) {
            case 'user_name':
                $likedQuery->join('users', 'audios.user_id', '=', 'users.id')
                           ->orderBy('users.name', $directionLiked)
                           ->select('audios.*');
                break;

            case 'category_name':
                $likedQuery->orderBy(
                    Category::select('name')
                        ->join('audio_category', 'categories.id', '=', 'audio_category.category_id')
                        ->whereColumn('audio_category.audio_id', 'audios.id')
                        ->limit(1),
                    $directionLiked
                );
                break;

            case 'tag_name':
                $likedQuery->orderBy(
                    Tag::select('name')
                        ->join('audio_tag', 'tags.id', '=', 'audio_tag.tag_id')
                        ->whereColumn('audio_tag.audio_id', 'audios.id')
                        ->limit(1),
                    $directionLiked
                );
                break;

            default:
                $likedQuery->orderBy($sortLiked, $directionLiked);
        }

        // PAGINACIÓN audios favoritos (cambiado nombre parámetro página)
        $likedAudios = $likedQuery->distinct()->paginate(5, ['*'], 'page_favoritos')->withQueryString();

        $totalAudios = Audio::where('user_id', $user->id)->count();

        $totalLikes = Audio::where('user_id', $user->id)
            ->withCount('likes')
            ->get()
            ->sum('likes_count');

        $categories = Category::orderBy('name')->get();

        return view('dashboard', compact(
            'audios', 'likedAudios', 'categories', 'totalAudios', 'totalLikes', 
            'sortUploaded', 'directionUploaded', 'sortLiked', 'directionLiked',
            'categoryUploaded', 'searchUploaded', 'categoryLiked', 'searchLiked'
        ));
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

        $audios = $query->latest()->paginate(perPage: 10); 

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

public function edit(Audio $audio)
{
    // Verificar si el usuario es el dueño del archivo
    if ($audio->user_id !== Auth::id()) {
        abort(403);
    }

    // Obtener las categorías y etiquetas relacionadas con el audio
    $categories = Category::all();
    $tags = $audio->tags->pluck('name')->implode(', ');

    // Pasar los datos al formulario de edición
    return view('audios.edit', compact('audio', 'categories', 'tags'));
}



    
}
