<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authorization berbasis role (kolom users.role: siswa | guru | ortu).
 * Contoh: ->middleware('role:guru')  atau  ->middleware('role:guru,siswa')
 * Pengguna yang belum login diarahkan ke /login; role yang tidak diizinkan mendapat 403.
 */
class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Silakan login terlebih dahulu.'], 401)
                : redirect('/login');
        }

        if (! in_array($user->role, $roles, true)) {
            abort(403, 'Peran "'.$user->role.'" tidak memiliki hak akses ke halaman ini.');
        }

        return $next($request);
    }
}
