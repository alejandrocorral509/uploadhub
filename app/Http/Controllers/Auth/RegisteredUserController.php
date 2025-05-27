<?php
namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Models\Role;  // Asegúrate de tener importado el modelo Role
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;


class RegisteredUserController extends Controller
{
    public function create()
    {
        return view('auth.register'); // Redirige a la vista del registro
    }

public function store(Request $request)
{
    // Validación de los datos
    $validated = $request->validate([
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
        'password' => ['required', 'string', 'min:8', 'confirmed'],
    ]);

    // Creamos el usuario con un rol por defecto (en este caso 'default')
    $role = Role::firstOrCreate(['name' => 'default']); // Si no existe el rol 'default', lo creamos
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'password' => Hash::make($validated['password']),
        'role_id' => $role->id,  // Asignamos el rol
    ]);

    // Mensaje flash para mostrar al llegar al login
    session()->flash('success', '¡Cuenta creada correctamente! Ahora puedes iniciar sesión.');

    // Redirigir al login
    return redirect()->route('login');
}

}


