<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * En API/SPA no redirigimos a una ruta 'login'; devolvemos 401 JSON.
     */
    protected function redirectTo($request): ?string
    {
        return null; // importante: evita Route [login] not defined
    }
}
