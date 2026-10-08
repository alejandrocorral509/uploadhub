<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Se perdió al sustituir la migración de usuarios por defecto; sin ella "¿Olvidaste tu contraseña?" falla
    public function up()
    {
        if (Schema::hasTable('password_reset_tokens')) {
            return;
        }

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
