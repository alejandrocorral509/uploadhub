<?php

namespace App\Http\Requests;

use App\Models\Show;
use Illuminate\Foundation\Http\FormRequest;

class ShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        $show = $this->route('show');

        return $show instanceof Show ? $this->user()->can('update', $show) : true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'language' => ['required', 'string', 'in:es,en,ca,gl,eu,pt,fr,it,de'],
            'explicit' => ['nullable', 'boolean'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=300,min_height=300'],
            'remove_cover' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'título',
            'description' => 'descripción',
            'category_id' => 'categoría',
            'language' => 'idioma',
            'cover' => 'portada',
        ];
    }

    public function messages(): array
    {
        return [
            'cover.dimensions' => 'La portada debe medir al menos 300×300 píxeles (Spotify y Apple piden 1400×1400).',
        ];
    }
}
