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
            // Cambiar columna youtube_id a youtube_url (string nullable)
            $table->dropColumn('youtube_id');
            $table->string('youtube_url')->nullable()->after('archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn('youtube_url');
            $table->string('youtube_id')->nullable()->after('archivo');
        });
    }
};
