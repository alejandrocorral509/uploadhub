<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function store(Request $request, Audio $audio): RedirectResponse
    {
        Gate::authorize('view', $audio);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ], attributes: ['content' => 'comentario']);

        $audio->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $data['content'],
        ]);

        return redirect()->to($audio->url().'#comentarios')->with('success', 'Comentario publicado.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $comment->load('audio');
        Gate::authorize('delete', $comment);

        $comment->delete();

        return back()->with('success', 'Comentario eliminado.');
    }
}
