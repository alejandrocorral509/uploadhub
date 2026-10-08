<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// Si un administrador bloquea a un usuario con la sesión abierta, se le cierra en la siguiente petición
class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->isBanned()) {
            if ($request->expectsJson()) {
                $user->currentAccessToken()?->delete();
                abort(403, 'Tu cuenta está bloqueada.');
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta está bloqueada.']);
        }

        return $next($request);
    }
}
