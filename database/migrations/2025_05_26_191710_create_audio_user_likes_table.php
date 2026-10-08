<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAudioUserLikesTable extends Migration
{
    public function up()
    {
        Schema::create('audio_user_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_id')->constrained('audios')->onDelete('cascade');  // Aquí audios, no audio
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Usuario (tabla users)
            $table->timestamps();
        });

    }

    public function down()
    {
        Schema::dropIfExists('audio_user_likes');
    }
}
