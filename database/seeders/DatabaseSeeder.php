<?php

// database/seeders/DatabaseSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(RolesSeeder::class);
        $this->call(CategoriesSeeder::class);

        // Crear un admin
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('admin1234'), // Cambia la contraseña según desees
            'role_id' => 1, // El id del role 'admin'
            'email_verified_at' => now(),
        ]);

        // Aquí puedes agregar más usuarios si lo deseas
    }
}

