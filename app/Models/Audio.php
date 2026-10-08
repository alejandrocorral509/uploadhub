<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Audio extends Model
{
    use HasFactory;

    protected $table = 'audios';

protected $fillable = [
    'user_id',
    'title',
    'filepath',
    'youtube_url',
    'description',
    'filetype',
    'thumbnail',
    'duration',
    'privacy',
    'cover',
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

public function likes()
{
    return $this->belongsToMany(User::class, 'audio_user_likes')->withTimestamps();
}


// Un audio privado solo lo puede ver su dueño
public function isVisibleTo($user): bool
{
    return $this->privacy === 'public' || ($user && $user->id === $this->user_id);
}

// Disco donde está el archivo: los nuevos van a 'local' (privado), los antiguos estaban en 'public'
public function fileDisk(): ?string
{
    if (!$this->filepath) {
        return null;
    }

    foreach (['local', 'public'] as $disk) {
        if (Storage::disk($disk)->exists($this->filepath)) {
            return $disk;
        }
    }

    return null;
}

public function deleteFile(): void
{
    if ($disk = $this->fileDisk()) {
        Storage::disk($disk)->delete($this->filepath);
    }
}

// Opcional: contar me gustas
public function likesCount()
{
    return $this->likes()->count();
}




}

