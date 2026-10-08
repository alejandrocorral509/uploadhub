<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->text('bio')->nullable()->after('email');
            $table->timestamp('banned_at')->nullable();
        });

        // Dar un slug a los usuarios que ya existían
        foreach (DB::table('users')->select('id', 'name')->get() as $user) {
            DB::table('users')->where('id', $user->id)->update([
                'slug' => Str::slug($user->name ?: 'usuario').'-'.$user->id,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn(['slug', 'bio', 'banned_at']);
        });
    }
};
