<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function create()
    {
        return view('auth.login'); // Vista de login
    }

    public function store(Request $request)
    {
        // Validación de los datos de login
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Intentar autenticar al usuario
            if (Auth::attempt($validated)) {
                session()->flash('success', '¡Has iniciado sesión correctamente!');
                return redirect()->intended(route('audios.public'));
            }



        // Si la autenticación falla, volver a mostrar el formulario con un error
        return back()->withErrors([
            'email' => __('The provided credentials do not match our records.'),
        ]);

        
    }

    public function destroy(Request $request)
    {
        Auth::logout(); // Cerrar sesión
        return redirect('/login');
    }
}
