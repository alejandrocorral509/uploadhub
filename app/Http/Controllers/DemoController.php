<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Demo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DemoController extends Controller
{
    // "Probar la demo": entra con la cuenta de ejemplo sin tener que registrarse
    public function login(Request $request): RedirectResponse
    {
        abort_unless(Demo::enabled(), 404);

        $user = User::where('email', config('demo.login_email'))->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('library')->with('success', "Has entrado como {$user->name}, una creadora de ejemplo. Mira sus estadísticas o sube un episodio.");
    }
}
