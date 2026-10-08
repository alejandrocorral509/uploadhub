<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::with('role')
            ->withCount(['audios', 'shows', 'comments'])
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($w) => $w->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")))
            ->when($request->query('filtro') === 'bloqueados', fn ($q) => $q->whereNotNull('banned_at'))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.users', ['users' => $users]);
    }

    public function ban(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'No puedes bloquearte a ti mismo.');

        $user->forceFill(['banned_at' => $user->isBanned() ? null : now()])->save();

        // Al bloquear, se cierran también sus sesiones de la API
        if ($user->isBanned()) {
            $user->tokens()->delete();
        }

        return back()->with('success', $user->isBanned() ? "{$user->name} ha sido bloqueado." : "{$user->name} ha sido desbloqueado.");
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'No puedes quitarte el rol de administrador a ti mismo.');

        $role = $user->isAdmin() ? Role::USER : Role::ADMIN;
        $user->forceFill(['role_id' => Role::firstOrCreate(['name' => $role])->id])->save();

        return back()->with('success', $role === Role::ADMIN ? "{$user->name} ahora es administrador." : "{$user->name} ya no es administrador.");
    }
}
