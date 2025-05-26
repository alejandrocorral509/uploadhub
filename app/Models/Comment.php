<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = [
        'user_id',
        'audio_id', // si usas
        'content',
    ];

    // Relación con audio (opcional)
    public function audio()
    {
        return $this->belongsTo(Audio::class);
    }

    // Relación con usuario (opcional)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
