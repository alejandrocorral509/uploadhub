<?php

use App\Models\Role;
use App\Models\User;
use App\Services\ChunkedUpload;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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

// Reinicio diario de la demo pública: borra todo y vuelve a cargar los datos de ejemplo
Artisan::command('demo:reset', function () {
    if (! config('demo.enabled')) {
        $this->error('El modo demo no está activado (DEMO_MODE=true). No se borra nada.');

        return 1;
    }

    foreach (array_unique([config('media.disk'), config('media.covers_disk')]) as $disk) {
        foreach (['audios', 'covers'] as $directory) {
            Storage::disk($disk)->deleteDirectory($directory);
        }
    }

    // Se vacían las tablas (sin borrarlas): el esquema no se toca
    $this->call('migrate', ['--force' => true]);
    // Solo las tablas de la base de datos de la app: en MySQL, getTables() lista las de todo el servidor
    $schema = DB::getDriverName() === 'sqlite' ? 'main' : DB::getDatabaseName();

    Schema::withoutForeignKeyConstraints(function () use ($schema) {
        collect(Schema::getTables())
            ->where('schema', $schema)
            ->pluck('name')
            ->reject(fn (string $table) => $table === 'migrations')
            ->each(fn (string $table) => DB::table($table)->delete());
    });

    $this->call('db:seed', ['--force' => true]);
    $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

    $this->info('Demo reiniciada.');
})->purpose('Borra todos los datos y vuelve a cargar la demo (solo con DEMO_MODE=true)');

Schedule::command('uploads:prune')->daily();
Schedule::command('demo:reset')->dailyAt(config('demo.reset_at'))->when(fn () => config('demo.enabled'));
