<?php

namespace App\Providers;

use App\Contracts\Summarizer;
use App\Contracts\Transcriber;
use App\Models\Audio;
use App\Models\Comment;
use App\Models\Show;
use App\Models\User;
use App\Services\Ai\ClaudeSummarizer;
use App\Services\Ai\DisabledAi;
use App\Services\Ai\WhisperTranscriber;
use App\Services\AudioProcessor;
use App\Services\ChunkedUpload;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AudioProcessor::class);
        $this->app->singleton(ChunkedUpload::class);

        $this->app->bind(Transcriber::class, fn ($app) => config('ai.transcription.driver') === 'openai'
            ? $app->make(WhisperTranscriber::class)
            : new DisabledAi);

        $this->app->bind(Summarizer::class, fn () => config('ai.summary.driver') === 'anthropic'
            ? new ClaudeSummarizer
            : new DisabledAi);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        // En la demo pública, subidas más pequeñas
        if (config('demo.enabled')) {
            config(['media.max_upload_mb' => min(config('media.max_upload_mb'), config('demo.max_upload_mb'))]);
        }

        // Fuera de producción, cargar una relación sin eager loading lanza un error:
        // así los problemas N+1 se ven en desarrollo y en los tests
        Model::preventLazyLoading(! $this->app->isProduction());

        // Nombres cortos y estables para las relaciones polimórficas (denuncias, tokens de la API)
        Relation::enforceMorphMap([
            'audio' => Audio::class,
            'show' => Show::class,
            'comment' => Comment::class,
            'user' => User::class,
        ]);

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        // Los administradores pueden moderar cualquier contenido
        Gate::before(function (User $user, string $ability) {
            if ($user->isAdmin() && in_array($ability, ['delete', 'moderate'], true)) {
                return true;
            }
        });

        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $byUser = fn (Request $request) => $request->user()?->id ?: $request->ip();

        RateLimiter::for('register', fn (Request $request) => Limit::perHour(5)->by($request->ip()));
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(6)->by($byUser($request)));
        RateLimiter::for('likes', fn (Request $request) => Limit::perMinute(60)->by($byUser($request)));
        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(20)->by($byUser($request)));
        RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(600)->by($byUser($request)));
        RateLimiter::for('plays', fn (Request $request) => Limit::perMinute(120)->by($byUser($request)));
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($byUser($request)));
        RateLimiter::for('api-tokens', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    }
}
