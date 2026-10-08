<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

// Denuncia de un episodio, programa o comentario
class Report extends Model
{
    public const REASONS = [
        'spam' => 'Spam o publicidad',
        'offensive' => 'Contenido ofensivo o de odio',
        'copyright' => 'Infringe derechos de autor',
        'misleading' => 'Información engañosa',
        'other' => 'Otro motivo',
    ];

    public const STATUS_OPEN = 'open';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_DISMISSED = 'dismissed';

    protected $fillable = ['user_id', 'reportable_type', 'reportable_id', 'reason', 'details', 'status', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function reportable(): MorphTo
    {
        return $this->morphTo();
    }

    // Carga lo denunciado con lo que necesita la vista (autor, episodio del comentario...)
    public function scopeWithReportable(Builder $query): Builder
    {
        return $query->with(['reporter', 'resolver', 'reportable' => function (MorphTo $morph) {
            $morph->morphWith([
                Audio::class => ['user'],
                Show::class => ['user'],
                Comment::class => ['user', 'audio'],
            ]);
        }]);
    }

    public function reasonLabel(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
