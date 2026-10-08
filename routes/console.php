<?php

use App\Models\Role;
use App\Models\User;
use App\Services\ChunkedUpload;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('uploads:prune', function (ChunkedUpload $uploads) {
    $this->info('Subidas abandonadas borradas: '.$uploads->prune());
})->purpose('Borra las subidas por trozos que llevan más de un día sin completarse');

Artisan::command('user:make-admin {email}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("No existe ningún usuario con el correo {$email}.");

        return 1;
    }

    $user->forceFill(['role_id' => Role::firstOrCreate(['name' => Role::ADMIN])->id])->save();
    $this->info("{$user->name} ahora es administrador.");
})->purpose('Da el rol de administrador a un usuario');

Schedule::command('uploads:prune')->daily();
