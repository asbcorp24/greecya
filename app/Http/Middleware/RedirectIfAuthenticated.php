<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                $route = $user->role === 'customer'
                    ? 'account.dashboard'
                    : ($user->role === 'accountant' ? 'admin.finance.index' : 'admin.dashboard');

                return redirect()->route($route);
            }
        }

        return $next($request);
    }
}
