<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesSeeder::class, CategoriesSeeder::class]);

        // El administrador se crea solo si se configuran sus datos en el .env:
        // así no hay credenciales conocidas en el código
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email && $password) {
            User::updateOrCreate(['email' => $email], [
                'name' => env('ADMIN_NAME') ?: 'Administración',
                'password' => $password,
                'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            ])->forceFill(['email_verified_at' => now()])->save();

            $this->command?->info("Administrador listo: {$email}");
        } else {
            $this->command?->warn('Sin ADMIN_EMAIL/ADMIN_PASSWORD en el .env: no se crea administrador. Usa "php artisan user:make-admin correo".');
        }
    }
}
