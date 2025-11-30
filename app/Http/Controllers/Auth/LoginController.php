<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($credentials, $request->remember)) {
            return back()->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // REDIRECT BASED ON ROLE
        if ($user->hasRole('job_seeker')) {
            return redirect()->intended('/seeker/dashboard');
        }

        if ($user->hasRole('company')) {
            return redirect()->intended('/employer/dashboard');
        }

        if ($user->hasRole('admin')) {
            return redirect()->intended('/admin');
        }

        // fallback
        return redirect('/seeker/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}

