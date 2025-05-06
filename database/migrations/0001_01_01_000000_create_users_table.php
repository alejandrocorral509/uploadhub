<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            $table->bigInteger('role_id')->unsigned();  // Asegúrate de que esté aquí
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');  // Esta es la clave foránea
        });
    }
    

  public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
