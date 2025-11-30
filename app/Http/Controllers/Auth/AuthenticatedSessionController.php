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
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required','string','email'],
            'password' => ['required','string'],
        ]);

        $credentials = $request->only('email','password');

        $remember = $request->filled('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Redirect based on role (if Spatie present & user has hasRole method)
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

        // If no roles or hasRole not available, fallback to RouteServiceProvider or /seeker/dashboard
        // Note: RouteServiceProvider::HOME often points to /dashboard — override to seeker
        return redirect()->intended('/seeker/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}

