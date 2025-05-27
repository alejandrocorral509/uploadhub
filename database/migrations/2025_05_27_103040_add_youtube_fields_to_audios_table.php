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
            // Cambiar 'archivo' por 'filepath' que sí existe
            $table->string('youtube_id')->nullable()->after('filepath');
            $table->enum('video_type', ['local', 'youtube'])->default('local')->after('filetype');
        });
    }

    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn(['youtube_id', 'video_type']);
        });
    }
};
