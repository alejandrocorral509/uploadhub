<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        // La categoría es obligatoria al subir un audio, así que tiene que haber alguna desde el principio
        $categories = [
            'Comedia',
            'Cultura',
            'Deportes',
            'Educación',
            'Entrevistas',
            'Historia',
            'Música',
            'Noticias',
            'Salud',
            'Tecnología',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }
    }
}
