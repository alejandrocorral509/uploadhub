<?php

use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/*
| Los tests de Feature usan una base de datos SQLite en memoria, discos falsos
| (nada se escribe en storage/) y los roles y categorías ya creados.
*/
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed([RolesSeeder::class, CategoriesSeeder::class]);
    })
    ->in('Feature');

function admin(): User
{
    return User::factory()->create([
        'role_id' => Role::where('name', Role::ADMIN)->value('id'),
    ]);
}

function categoryIds(int $count = 1): array
{
    return Category::limit($count)->pluck('id')->all();
}

// Imagen real (no hace falta la extensión GD, que no siempre está instalada)
function fixtureImage(string $name): UploadedFile
{
    $copy = tempnam(sys_get_temp_dir(), 'img');
    copy(__DIR__.'/fixtures/'.$name, $copy);

    return new UploadedFile($copy, $name, 'image/jpeg', null, true);
}
