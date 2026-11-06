<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsWaka
{
    /**
     * Handle an incoming request.
     * Pastikan hanya user dengan role_id == 1 (Waka Kurikulum / Admin) yang dapat mengakses area website ini.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->role_id != 1) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Akses website ini dikunci khusus untuk Waka Kurikulum / Admin. Akun pengguna lain diarahkan menggunakan aplikasi mobile / klien.',
            ]);
        }

        return $next($request);
    }
}
