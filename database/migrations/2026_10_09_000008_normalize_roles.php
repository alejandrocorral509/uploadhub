<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // El registro antiguo asignaba un rol "default" que el resto de la app no conocía: pasa a ser "user"
    public function up(): void
    {
        foreach (['admin', 'user'] as $name) {
            if (! DB::table('roles')->where('name', $name)->exists()) {
                DB::table('roles')->insert(['name' => $name, 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        $userRole = DB::table('roles')->where('name', 'user')->value('id');
        $legacy = DB::table('roles')->whereNotIn('name', ['admin', 'user'])->pluck('id');

        if ($legacy->isNotEmpty()) {
            DB::table('users')->whereIn('role_id', $legacy)->update(['role_id' => $userRole]);
            DB::table('roles')->whereIn('id', $legacy)->delete();
        }

        // Usuarios sin un rol válido (la clave foránea se borró en el TFG original)
        DB::table('users')->whereNotIn('role_id', DB::table('roles')->select('id'))->update(['role_id' => $userRole]);
    }

    public function down(): void
    {
        //
    }
};
