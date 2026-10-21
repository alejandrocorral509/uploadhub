<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Cada usuario solo puede dar un me gusta a cada episodio
    public function up(): void
    {
        $keep = DB::table('audio_user_likes')
            ->selectRaw('MIN(id) as id')
            ->groupBy('audio_id', 'user_id')
            ->pluck('id');

        DB::table('audio_user_likes')->whereNotIn('id', $keep)->delete();

        Schema::table('audio_user_likes', function (Blueprint $table) {
            $table->unique(['audio_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('audio_user_likes', function (Blueprint $table) {
            // MySQL usa el índice único para la clave foránea de audio_id: hace falta otro antes de quitarlo
            $table->index('audio_id');
            $table->dropUnique(['audio_id', 'user_id']);
        });
    }
};
