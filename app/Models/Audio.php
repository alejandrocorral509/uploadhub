<?php

namespace App\Models;

use App\Support\Media;
use App\Support\YouTube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Un episodio. La tabla se sigue llamando "audios" por compatibilidad con el TFG original.
class Audio extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_READY = 'ready';

    public const STATUS_FAILED = 'failed';

    protected $table = 'audios';

    protected $fillable = [
        'user_id',
        'show_id',
        'season',
        'episode_number',
        'title',
        'filepath',
        'youtube_url',
        'description',
        'filetype',
        'duration',
        'privacy',
        'cover',
        'processing_status',
        'waveform',
        'transcript_status',
        'transcript',
        'summary',
        'chapters',
    ];

    protected function casts(): array
    {
        return [
            'duration' => 'integer',
            'waveform' => 'array',
            'chapters' => 'array',
            'plays_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Al borrar un episodio se borran también su archivo y su portada
        static::deleting(function (Audio $audio) {
            $audio->deleteFile();
            $audio->deleteCover();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withTimestamps();
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'audio_user_likes')->withTimestamps();
    }

    public function plays(): HasMany
    {
        return $this->hasMany(Play::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    // Episodios que puede ver un usuario: los públicos y, si hay sesión, los suyos
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $q) use ($user) {
            $q->where('privacy', 'public');
            if ($user) {
                $q->orWhere('audios.user_id', $user->id);
            }
        });
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('privacy', 'public');
    }

    // Busca en título, descripción, etiquetas, autor, programa y transcripción
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);
        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('title', 'like', $like)
                ->orWhere('description', 'like', $like)
                ->orWhere('transcript', 'like', $like)
                ->orWhereHas('tags', fn (Builder $t) => $t->where('name', 'like', $like))
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $like))
                ->orWhereHas('show', fn (Builder $s) => $s->where('title', 'like', $like));
        });
    }

    public function isVisibleTo(?User $user): bool
    {
        return $this->privacy === 'public' || ($user && $user->id === $this->user_id);
    }

    public function isPublic(): bool
    {
        return $this->privacy === 'public';
    }

    public function isYoutube(): bool
    {
        return ! $this->filepath && $this->youtube_url;
    }

    public function isVideo(): bool
    {
        return Str::startsWith((string) $this->filetype, 'video/');
    }

    public function isReady(): bool
    {
        return $this->processing_status === self::STATUS_READY;
    }

    public function youtubeId(): ?string
    {
        return YouTube::id($this->youtube_url);
    }

    public function slug(): string
    {
        return Str::slug($this->title) ?: 'episodio';
    }

    public function url(): string
    {
        return route('episodes.show', ['audio' => $this, 'slug' => $this->slug()]);
    }

    // Portada propia, si no la del programa y, si es de YouTube, la miniatura del vídeo
    public function coverUrl(): ?string
    {
        if ($this->cover) {
            return Media::publicUrl(config('media.covers_disk'), $this->cover);
        }

        if ($this->show?->cover) {
            return $this->show->coverUrl();
        }

        if ($id = $this->youtubeId()) {
            return YouTube::thumbnailUrl($id);
        }

        return null;
    }

    public function formattedDuration(): ?string
    {
        if (! $this->duration) {
            return null;
        }

        $h = intdiv($this->duration, 3600);
        $m = intdiv($this->duration % 3600, 60);
        $s = $this->duration % 60;

        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    public function episodeLabel(): ?string
    {
        if (! $this->episode_number) {
            return null;
        }

        return $this->season ? "T{$this->season} · E{$this->episode_number}" : "Episodio {$this->episode_number}";
    }

    // Datos que necesita el reproductor global
    public function playerPayload(?int $resumeAt = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->show?->title ?? $this->user?->name,
            'cover' => $this->coverUrl(),
            'src' => route('audios.stream', $this),
            'url' => $this->url(),
            'duration' => $this->duration,
            'isVideo' => $this->isVideo(),
            'resumeAt' => $resumeAt,
            'playsUrl' => route('plays.store', $this),
        ];
    }

    // Disco donde está el archivo: el configurado y, por compatibilidad, 'public' (subidas antiguas)
    public function fileDisk(): ?string
    {
        if (! $this->filepath) {
            return null;
        }

        foreach (array_unique([config('media.disk'), 'local', 'public']) as $disk) {
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

    public function deleteCover(): void
    {
        if ($this->cover) {
            Storage::disk(config('media.covers_disk'))->delete($this->cover);
        }
    }
}
