<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name'];

    public function audios(): BelongsToMany
    {
        return $this->belongsToMany(Audio::class, 'audio_category')->withTimestamps();
    }

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }
}
