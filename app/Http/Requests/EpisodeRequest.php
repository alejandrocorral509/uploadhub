<?php

namespace App\Http\Requests;

use App\Models\Audio;
use App\Rules\YouTubeUrl;
use App\Services\ChunkedUpload;
use App\Support\Demo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

// Validación común para crear y editar un episodio
class EpisodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $audio = $this->route('audio');

        return $audio instanceof Audio ? $this->user()->can('update', $audio) : true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'show_id' => $this->input('show_id') ?: null,
            'season' => $this->input('season') ?: null,
            'episode_number' => $this->input('episode_number') ?: null,
            'youtube_url' => $this->input('youtube_url') ?: null,
            'upload_token' => $this->input('upload_token') ?: null,
        ]);
    }

    public function rules(): array
    {
        $maxKb = config('media.max_upload_mb') * 1024;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'show_id' => ['nullable', Rule::exists('shows', 'id')->where('user_id', $this->user()->id)],
            'season' => ['nullable', 'integer', 'min:1', 'max:999'],
            'episode_number' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'privacy' => ['required', Rule::in(['public', 'private'])],
            'categories' => ['required', 'array', 'min:1', 'max:3'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'tags' => ['nullable', 'string', 'max:500'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover' => ['nullable', 'boolean'],

            // Origen del audio: archivo subido de una vez, subida por trozos o YouTube
            'audio' => ['nullable', 'file', 'mimes:'.implode(',', config('media.allowed_extensions')), 'max:'.$maxKb],
            'upload_token' => ['nullable', 'uuid'],
            'youtube_url' => ['nullable', 'string', 'max:255', new YouTubeUrl],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sources = array_filter([
                    $this->hasFile('audio'),
                    $this->filled('upload_token'),
                    $this->filled('youtube_url'),
                ]);

                $isCreate = ! $this->route('audio');

                if ($isCreate && ($limit = Demo::limitMessage($this->user(), 'episodes'))) {
                    $validator->errors()->add('audio', $limit);
                }

                if ($isCreate && count($sources) === 0) {
                    $validator->errors()->add('audio', 'Sube un archivo de audio o pega un enlace de YouTube.');
                }

                if (count($sources) > 1) {
                    $validator->errors()->add('audio', 'Elige solo un origen: archivo o enlace de YouTube.');
                }

                if ($this->filled('upload_token') && ! app(ChunkedUpload::class)->isComplete($this->input('upload_token'), $this->user())) {
                    $validator->errors()->add('audio', 'La subida del archivo no se ha completado. Vuelve a intentarlo.');
                }

                foreach ($this->tagNames() as $tag) {
                    if (mb_strlen($tag) > 50) {
                        $validator->errors()->add('tags', 'Cada etiqueta puede tener como máximo 50 caracteres.');
                        break;
                    }
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descripción',
            'show_id' => 'programa',
            'season' => 'temporada',
            'episode_number' => 'número de episodio',
            'privacy' => 'privacidad',
            'categories' => 'categorías',
            'tags' => 'etiquetas',
            'cover' => 'portada',
            'audio' => 'archivo de audio',
            'youtube_url' => 'enlace de YouTube',
        ];
    }

    /** @return array<int, string> */
    public function tagNames(): array
    {
        return collect(explode(',', (string) $this->input('tags')))
            ->map(fn ($tag) => trim($tag))
            ->filter()
            ->unique(fn ($tag) => mb_strtolower($tag))
            ->take(10)
            ->values()
            ->all();
    }
}
