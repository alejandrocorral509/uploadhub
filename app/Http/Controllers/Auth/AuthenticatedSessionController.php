<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    // LoginRequest limita los intentos (5 por correo e IP) y rechaza las cuentas bloqueadas
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Nuevo ID de sesión al entrar para evitar fijación de sesión
        $request->session()->regenerate();

        return redirect()->intended(route('home'))->with('success', '¡Hola de nuevo, '.$request->user()->name.'!');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
