<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audio extends Model
{
    use HasFactory;

    // Campos asignables masivamente
    protected $fillable = ['titulo', 'archivo', 'user_id'];

    // Relación con el modelo User (un audio pertenece a un usuario)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
