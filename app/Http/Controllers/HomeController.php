<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class HomeController extends Controller
{
    public function index()
    {
        // Obtener categorías con sus audios más populares (puedes ordenar por likes o fecha)
        $genres = Category::with(['audios' => function($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }])->get();

        return view('home', compact('genres'));
    }
}
