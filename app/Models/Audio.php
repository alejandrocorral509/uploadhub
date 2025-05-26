<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    use HasFactory;

    protected $table = 'audios';

    protected $fillable = [
        'user_id',
        'titulo',      // usa 'titulo' para el título
        'archivo',     // usa 'archivo' para el path/nombre del archivo
        'description',
        'filetype',
        'thumbnail',
        'duration',
        'privacy',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
{
    return $this->belongsToMany(Category::class);
}

public function tags()
{
    return $this->belongsToMany(Tag::class);
}

public function comments()
{
    return $this->hasMany(Comment::class);
}

    public function likedUsers()
    {
        return $this->belongsToMany(User::class, 'audio_user_likes', 'audio_id', 'user_id')->withTimestamps();
    }




}

