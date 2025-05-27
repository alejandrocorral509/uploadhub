<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name'];

public function audios()
{
    return $this->belongsToMany(Audio::class, 'audio_category'); // Ajusta el nombre de la tabla pivote si es distinto
}

}
