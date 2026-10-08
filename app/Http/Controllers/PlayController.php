<?php

namespace App\Http\Controllers;

use App\Models\Audio;
use App\Models\Play;
use App\Models\PlaybackProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Escuchas para las estadísticas y el "continuar donde lo dejaste".
 *
 * El reproductor avisa al empezar (store) y cada 15 segundos envía la posición (update).
 * Una persona que vuelve a darle al play antes de 30 minutos no cuenta como escucha nueva.
 */
class PlayController extends Controller
{
    public function store(Request $request, Audio $audio): JsonResponse
    {
        Gate::authorize('view', $audio);

        $hash = $this->listenerHash($request);

        $play = Play::where('audio_id', $audio->id)
            ->where('listener_hash', $hash)
            ->where('updated_at', '>=', now()->subMinutes(30))
            ->latest('id')
            ->first();

        if (! $play) {
            $play = Play::create([
                'audio_id' => $audio->id,
                'user_id' => $request->user()?->id,
                'listener_hash' => $hash,
            ]);
            $audio->increment('plays_count');
        }

        return response()->json([
            'play' => $play->id,
            'update_url' => route('plays.update', $play),
        ]);
    }

    public function update(Request $request, Play $play): JsonResponse
    {
        abort_unless(hash_equals($play->listener_hash, $this->listenerHash($request)), 403);

        $data = $request->validate([
            'position' => ['required', 'integer', 'min:0', 'max:86400'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $position = (int) $data['position'];
        $play->update(['max_position' => max($play->max_position, $position)]);

        $audio = $play->audio()->first();

        // Si aún no se conocía la duración (sin ffmpeg), se toma la que informa el navegador
        if ($audio && ! $audio->duration && ! empty($data['duration'])) {
            $audio->update(['duration' => (int) $data['duration']]);
        }

        if ($user = $request->user()) {
            $duration = $audio?->duration ?: (int) ($data['duration'] ?? 0);
            PlaybackProgress::updateOrCreate(
                ['user_id' => $user->id, 'audio_id' => $play->audio_id],
                ['position' => $position, 'completed' => $duration > 0 && $position >= $duration - 30],
            );
        }

        return response()->json(['ok' => true]);
    }

    // Identifica al oyente sin guardar datos personales: usuario o sesión, cifrado con la clave de la app
    private function listenerHash(Request $request): string
    {
        $id = $request->user() ? 'u'.$request->user()->id : 's'.$request->session()->getId();

        return hash_hmac('sha256', $id, config('app.key'));
    }
}
