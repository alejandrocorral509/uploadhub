<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    protected $fillable = [
        'name', 'email', 'password', 'role_id',  // Asegúrate de incluir 'role_id'
    ];
    
    // Si estás usando relaciones con el modelo Role
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

