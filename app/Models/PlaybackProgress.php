<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaybackProgress extends Model
{
    protected $table = 'playback_progress';

    protected $fillable = ['user_id', 'audio_id', 'position', 'completed'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'completed' => 'boolean'];
    }

    /**
     * Posición guardada de cada episodio de la lista para un usuario, en una sola consulta.
     * Los episodios terminados no se incluyen (empiezan desde el principio).
     *
     * @return array<int, int> audio_id => segundos
     */
    public static function positionsFor(?User $user, iterable $audios): array
    {
        $ids = collect($audios)->pluck('id')->all();
        if (! $user || $ids === []) {
            return [];
        }

        return static::where('user_id', $user->id)
            ->whereIn('audio_id', $ids)
            ->where('completed', false)
            ->pluck('position', 'audio_id')
            ->all();
    }

    public function audio(): BelongsTo
    {
        return $this->belongsTo(Audio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
