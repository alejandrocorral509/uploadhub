<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una fila por cada vez que alguien empieza a escuchar un episodio
        Schema::create('plays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('audio_id')->constrained('audios')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('listener_hash', 64);
            $table->unsignedInteger('max_position')->default(0);
            $table->timestamps();
            $table->index(['audio_id', 'created_at']);
        });

        // Dónde se quedó cada usuario en cada episodio, para continuar desde cualquier dispositivo
        Schema::create('playback_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('audio_id')->constrained('audios')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'audio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('playback_progress');
        Schema::dropIfExists('plays');
    }
};
