<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
     // Permitir la asignación masiva para el campo 'name'
    protected $fillable = ['name'];
    // Relación con el modelo User (un rol tiene muchos usuarios)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
