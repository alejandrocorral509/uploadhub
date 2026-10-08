<?php

namespace App\Models;

use App\Support\Media;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Un programa de podcast: agrupa episodios y tiene su propio feed RSS
class Show extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'description',
        'cover',
        'language',
        'explicit',
    ];

    protected function casts(): array
    {
        return [
            'explicit' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Show $show) {
            $show->slug ??= static::uniqueSlug($show->title);
        });

        static::deleting(function (Show $show) {
            if ($show->cover) {
                Storage::disk(config('media.covers_disk'))->delete($show->cover);
            }
        });
    }

    public static function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'programa';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function episodes(): HasMany
    {
        return $this->hasMany(Audio::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'subscriptions')->withTimestamps();
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function coverUrl(): ?string
    {
        return $this->cover ? Media::publicUrl(config('media.covers_disk'), $this->cover) : null;
    }

    public function feedUrl(): string
    {
        return route('shows.feed', $this);
    }
}
