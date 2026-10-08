<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    // Tras registrarse se vuelve al login con un aviso (el rol "user" lo asigna el modelo)
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], attributes: ['name' => 'nombre', 'password' => 'contraseña']);

        $user = User::create($validated);

        event(new Registered($user));

        return redirect()->route('login')->with('success', '¡Cuenta creada correctamente! Ahora puedes iniciar sesión.');
    }
}
