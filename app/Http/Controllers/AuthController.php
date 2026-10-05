<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('email'));
        $field = filter_var($loginInput, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $credentials = [
            $field => $loginInput,
            'password' => $request->input('password'),
            'is_active' => true,
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        // Check if user exists and is inactive to provide precise feedback
        $inactiveUser = \App\Models\User::where($field, $loginInput)->first();
        if ($inactiveUser && \Illuminate\Support\Facades\Hash::check($request->input('password'), $inactiveUser->password) && !$inactiveUser->is_active) {
            return back()->withErrors([
                'email' => 'Akun Anda dinonaktifkan oleh Administrator. Hubungi administrator untuk mengaktifkan kembali.',
            ])->onlyInput('email');
        }

        return back()->withErrors([
            'email' => 'Email/Nomor telepon atau password yang dimasukkan salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
