<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Tampilkan halaman login.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Proses permintaan login.
     */
    public function store(Request $request)
    {
        // Validasi input
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->filled('remember');

        // Coba login
        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        // Regenerate session agar aman
        $request->session()->regenerate();

        $user = Auth::user();

        /**
         * 1. PRIORITAS: redirect ke URL "intended" dari form (jika ada).
         *    Ini yang dipakai ketika kita kirim param intended dari:
         *    /login?intended=https://teleworks.id/loker/123
         *    lalu di form login kita lempar ke input hidden "intended".
         */
        if ($request->filled('intended')) {
            $intended = $request->input('intended');

            // Guard sederhana: hanya izinkan redirect ke domain sendiri
            if (! str_starts_with($intended, url('/'))) {
                $intended = url('/');
            }

            return redirect($intended);
        }

        /**
         * 2. Kalau tidak ada "intended" manual, pakai role-based redirect.
         */
        if ($user && method_exists($user, 'hasRole')) {
            if ($user->hasRole('job_seeker')) {
                return redirect()->intended('/seeker/dashboard');
            }

            if ($user->hasRole('company')) {
                return redirect()->intended('/employer/dashboard');
            }

            if ($user->hasRole('admin')) {
                return redirect()->intended('/admin');
            }
        }

        /**
         * 3. Fallback: kalau user tidak punya role atau method hasRole tidak ada,
         *    arahkan ke default dashboard pencari kerja.
         */
        return redirect()->intended('/seeker/dashboard');
        // Atau kalau mau pakai constant:
        // return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Logout user.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

