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
        $table->string('title')->nullable()->after('user_id');
        $table->text('description')->nullable()->after('title');
    });
}

public function down()
{
    Schema::table('audios', function (Blueprint $table) {
        $table->dropColumn(['title', 'description']);
    });
}


};
