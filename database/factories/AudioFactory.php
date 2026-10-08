<?php

namespace Database\Factories;

use App\Models\Audio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends Factory<Audio>
 */
class AudioFactory extends Factory
{
    protected $model = Audio::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'privacy' => 'public',
            'filetype' => 'audio/mpeg',
            'processing_status' => Audio::STATUS_READY,
            'duration' => 600,
        ];
    }

    // Con un archivo real en el disco de medios
    public function withFile(string $content = 'ID3 audio de prueba'): static
    {
        return $this->state(function () use ($content) {
            $path = 'audios/'.fake()->uuid().'.mp3';
            Storage::disk(config('media.disk'))->put($path, $content);

            return ['filepath' => $path];
        });
    }

    public function private(): static
    {
        return $this->state(['privacy' => 'private']);
    }

    public function youtube(string $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'): static
    {
        return $this->state(['filepath' => null, 'filetype' => 'youtube', 'youtube_url' => $url, 'duration' => null]);
    }
}
