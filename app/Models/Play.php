<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Una escucha de un episodio. max_position es hasta dónde llegó, para la curva de retención.
class Play extends Model
{
    protected $fillable = ['audio_id', 'user_id', 'listener_hash', 'max_position'];

    protected function casts(): array
    {
        return ['max_position' => 'integer'];
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
