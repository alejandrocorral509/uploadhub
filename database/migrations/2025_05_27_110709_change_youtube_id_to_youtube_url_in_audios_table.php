<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('audios', function (Blueprint $table) {
            // Primero eliminamos la columna 'youtube_id' si existe
            if (Schema::hasColumn('audios', 'youtube_id')) {
                $table->dropColumn('youtube_id');
            }
            // Luego agregamos 'youtube_url' después de 'filepath'
            $table->string('youtube_url')->nullable()->after('filepath');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn('youtube_url');
            $table->string('youtube_id')->nullable()->after('filepath');
        });
    }
};
