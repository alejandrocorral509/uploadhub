<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\User;
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

    // También carga categorías para el formulario
    $categories = Category::orderBy('name')->get();

    return view('dashboard', compact('audios', 'categories'));
}


public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'audio' => 'required|file|mimes:mp3,wav,m4a,mp4,ogg|max:51200',
        'description' => 'nullable|string',
        'privacy' => 'required|in:public,private',
        'categories' => 'nullable|array',
        'categories.*' => 'exists:categories,id',
        'tags' => 'nullable|string',
    ]);

    $file = $request->file('audio');
    $filepath = $file->store('audios', 'public');

    // Crear el audio en la base de datos
    $audio = Audio::create([
        'user_id' => Auth::id(),
        'titulo' => $request->input('title'),
        'archivo' => $filepath,
        'description' => $request->input('description'),
        'filetype' => $file->getClientMimeType(),
        'privacy' => $request->input('privacy'),
    ]);

    // Sincronizar categorías
    if ($request->has('categories')) {
        $audio->categories()->sync($request->input('categories'));
    }

    // Para etiquetas (texto separado por comas)
    if ($request->has('tags')) {
        $tags = collect(explode(',', $request->input('tags')))
            ->map(fn($tag) => trim($tag))
            ->filter()
            ->unique();

        $tagIds = $tags->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName])->id;
        });

        $audio->tags()->sync($tagIds);
    }

    return redirect()->route('dashboard')->with('success', 'Audio subido correctamente.');
}


    public function destroy(Audio $audio)
    {
        if ($audio->user_id !== Auth::id()) {
            abort(403);
        }

        Storage::disk('public')->delete($audio->archivo);
        $audio->delete();

        return back()->with('success', 'Audio eliminado correctamente.');
    }

    public function publicIndex()
    {
        $audios = Audio::where('privacy', 'public')
                    ->with(['categories', 'tags', 'user'])
                    ->latest()
                    ->paginate(12); // Paginación para no cargar todo de golpe

        return view('audios.public', compact('audios'));
    }
    public function toggleLike(Audio $audio)
    {
        $user = Auth::user();

        if ($audio->likedUsers()->where('user_id', $user->id)->exists()) {
            // Si ya le gustó, eliminar el like
            $audio->likedUsers()->detach($user->id);
            return response()->json(['message' => 'Like removed']);
        } else {
            // Si no le gustó, agregar el like
            $audio->likedUsers()->attach($user->id);
            return response()->json(['message' => 'Like added']);
        }
    }




}
