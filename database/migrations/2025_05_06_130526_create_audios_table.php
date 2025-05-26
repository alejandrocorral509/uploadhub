<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->string('title')->nullable()->after('user_id');
            $table->text('description')->nullable()->after('title');
            $table->string('filetype')->nullable()->after('filepath');
            $table->string('thumbnail')->nullable()->after('filetype');
            $table->integer('duration')->nullable()->after('thumbnail');
            $table->enum('privacy', ['public', 'private'])->default('public')->after('duration');
        });
    }

    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'filetype', 'thumbnail', 'duration', 'privacy']);
        });
    }

};

