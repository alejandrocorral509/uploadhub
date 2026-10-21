<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\ProtectDemoAccounts;
use App\Http\Middleware\UseSanctumGuard;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'demo.protect' => ProtectDemoAccounts::class,
        ]);

        $middleware->appendToGroup('web', EnsureUserIsNotBanned::class);
        $middleware->prependToGroup('api', UseSanctumGuard::class);
        $middleware->appendToGroup('api', EnsureUserIsNotBanned::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
