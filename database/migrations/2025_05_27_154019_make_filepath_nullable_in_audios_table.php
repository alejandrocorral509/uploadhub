<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->string('filepath')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('audios', function (Blueprint $table) {
            $table->string('filepath')->nullable(false)->change();
        });
    }
};
