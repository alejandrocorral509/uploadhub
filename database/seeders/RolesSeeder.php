<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([Role::ADMIN, Role::USER] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
