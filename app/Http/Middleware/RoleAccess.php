<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleAccess
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $userRole = auth()->user()->role;

        if ($userRole === 'Admin' || in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Anda tidak punya akses.');
    }
}
