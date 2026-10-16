<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (! auth()->attempt([...$credentials, 'is_active' => true], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'E-posta veya şifre hatalı.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('login_at', now()->timestamp + 1);
        $request->user()->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
