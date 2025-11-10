<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = auth()->user();

        // Jika belum login
        if (!$user) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        // Cek jika user memiliki role langsung
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // Cek akses spesifik berdasarkan flag di tabel users
        foreach ($roles as $role) {
            if (property_exists($user, $role) && $user->{$role}) {
                return $next($request);
            }
        }

        // Tidak memenuhi kriteria apa pun
        abort(403, 'Anda tidak memiliki akses.');
    }
}
