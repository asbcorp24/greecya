<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function create() { return view('auth.login'); }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Неверный логин или пароль.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $role = $request->user()->role;
        $route = $role === 'customer' ? 'account.dashboard' : ($role === 'accountant' ? 'admin.finance.index' : 'admin.dashboard');

        return redirect()->intended(route($route));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('home');
    }
}
