<?php

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
    ->withMiddleware(function (Middleware $middleware): void {
        // Aliases de middleware (Laravel 11+)
        $middleware->alias([
            'auth'      => \App\Http\Middleware\Authenticate::class, // importante: evita redirección a route('login') en API
            'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class, // importante: scope multiple
            'ability'   => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class, // importante: any scope
            // Spatie Permission (namespace fix)
            'role'                 => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'           => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission'   => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Forzar JSON en endpoints API para evitar redirección a 'login'
        $exceptions->shouldRenderJsonWhen(function ($request, \Throwable $e) {
            return $request->is('api/*');
        });

        // Responder 401 en autenticación fallida
        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return null; // usar comportamiento por defecto para no-API
        });
    })->create();
