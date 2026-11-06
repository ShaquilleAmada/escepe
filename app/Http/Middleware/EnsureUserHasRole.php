<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware: Proteksi Role untuk API
 *
 * Digunakan untuk membatasi akses endpoint berdasarkan role_id pengguna.
 * Cara penggunaan di route:
 *   ->middleware('role:1')          // hanya role_id = 1
 *   ->middleware('role:1,2')        // role_id 1 atau 2
 *   ->middleware('role:1,5,6')      // role_id 1, 5, atau 6
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum login atau token tidak valid.',
            ], 401);
        }

        // Konversi semua role ke integer untuk perbandingan
        $allowedRoles = array_map('intval', $roles);

        if (!in_array((int) $user->role_id, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang untuk mengakses endpoint ini.',
            ], 403);
        }

        return $next($request);
    }
}
