<?php

namespace App\Http\Middleware;

use App\Support\Demo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// En la demo, las cuentas de ejemplo las comparte todo el mundo: nadie puede cambiar
// su contraseña, su correo ni borrarlas (dejarían la demo inservible para los demás)
class ProtectDemoAccounts
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Demo::isDemoAccount($request->user())) {
            return back()->with('error', 'Esta es una cuenta de demostración compartida: sus datos de acceso no se pueden cambiar. Crea tu propia cuenta para probarlo.');
        }

        return $next($request);
    }
}
