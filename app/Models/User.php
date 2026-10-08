<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'slug', 'email', 'bio', 'password', 'role_id',
    ];

    // Nunca deben salir en una respuesta JSON (por ejemplo, en la API)
    protected $hidden = [
        'password', 'remember_token', 'email', 'role_id', 'banned_at', 'email_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'banned_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->role_id ??= Role::firstOrCreate(['name' => Role::USER])->id;
            $user->slug ??= static::uniqueSlug($user->name);
        });

        // Borrar uno a uno para que cada episodio y programa borre sus archivos
        static::deleting(function (User $user) {
            $user->audios()->get()->each->delete();
            $user->shows()->get()->each->delete();
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'usuario';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function audios(): HasMany
    {
        return $this->hasMany(Audio::class);
    }

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likedAudios(): BelongsToMany
    {
        return $this->belongsToMany(Audio::class, 'audio_user_likes')->withTimestamps();
    }

    public function subscriptions(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'subscriptions')->withTimestamps();
    }

    public function playbackProgress(): HasMany
    {
        return $this->hasMany(PlaybackProgress::class);
    }

    private ?bool $isAdminCache = null;

    public function isAdmin(): bool
    {
        if ($this->relationLoaded('role')) {
            return $this->role?->name === Role::ADMIN;
        }

        return $this->isAdminCache ??= Role::whereKey($this->role_id)->value('name') === Role::ADMIN;
    }

    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    public function initials(): string
    {
        return Str::upper(collect(explode(' ', trim($this->name)))->take(2)->map(fn ($p) => Str::substr($p, 0, 1))->implode(''));
    }

    public function profileUrl(): string
    {
        return route('profiles.show', $this);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
