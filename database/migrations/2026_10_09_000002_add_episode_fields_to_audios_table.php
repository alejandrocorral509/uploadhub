<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->foreignId('show_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('season')->nullable()->after('show_id');
            $table->unsignedSmallInteger('episode_number')->nullable()->after('season');

            // Procesado en segundo plano: duración, conversión a MP3, volumen y forma de onda
            $table->string('processing_status', 20)->default('ready')->after('filetype');
            $table->json('waveform')->nullable();
            $table->unsignedInteger('plays_count')->default(0);

            // Transcripción y resumen con IA
            $table->string('transcript_status', 20)->nullable();
            $table->longText('transcript')->nullable();
            $table->text('summary')->nullable();
            $table->json('chapters')->nullable();

            $table->index(['privacy', 'created_at']);
        });

        // Columnas que nunca se llegaron a usar
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn(['thumbnail', 'video_type']);
        });
    }

    public function down(): void
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropIndex(['privacy', 'created_at']);
            $table->dropConstrainedForeignId('show_id');
            $table->dropColumn([
                'season', 'episode_number', 'processing_status', 'waveform', 'plays_count',
                'transcript_status', 'transcript', 'summary', 'chapters',
            ]);
            $table->string('thumbnail')->nullable();
            $table->enum('video_type', ['local', 'youtube'])->default('local');
        });
    }
};
