<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            if (Auth::user()->role_id == 1) {
                return redirect()->route('waka.dashboard');
            }
            Auth::logout();
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($credentials['username']);
        $user = User::where('username', $loginInput)
            ->orWhere('nip_nisn', $loginInput)
            ->first();

        if ($user && Hash::check($credentials['password'], $user->password)) {
            Auth::login($user, $request->filled('remember'));
            $user = Auth::user();

            // Kunci Web Laravel Hanya Khusus untuk Waka Kurikulum / Admin (Role ID 1)
            if ($user->role_id != 1) {
                $roleName = $user->role->role_name ?? 'Pengguna Lain';
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'username' => 'Akses ditolak! Website ini dikunci khusus untuk Waka Kurikulum / Admin. Akun Anda terdaftar sebagai ' . $roleName . ' (' . $user->fullname . ') dan hanya diizinkan mengakses via aplikasi mobile/klien.',
                ])->onlyInput('username');
            }

            $request->session()->regenerate();
            return redirect()->intended(route('waka.dashboard'))
                ->with('success', 'Selamat datang, ' . $user->fullname);
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'Anda telah berhasil logout.');
    }
}
