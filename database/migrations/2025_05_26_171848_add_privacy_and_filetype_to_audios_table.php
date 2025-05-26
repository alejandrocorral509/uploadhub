<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audios', function (Blueprint $table) {
            // Solo si no existen ya las columnas, si no están crea
            if (!Schema::hasColumn('audios', 'filetype')) {
                $table->string('filetype')->nullable()->after('archivo');
            }
            if (!Schema::hasColumn('audios', 'privacy')) {
                $table->enum('privacy', ['public', 'private'])->default('public')->after('filetype');
            }
        });
    }

    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            if (Schema::hasColumn('audios', 'privacy')) {
                $table->dropColumn('privacy');
            }
            if (Schema::hasColumn('audios', 'filetype')) {
                $table->dropColumn('filetype');
            }
        });
    }
};
