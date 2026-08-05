<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CustomerMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        abort_unless($request->user() && $request->user()->role === 'customer' && $request->user()->customer_id, 403);
        return $next($request);
    }
}
