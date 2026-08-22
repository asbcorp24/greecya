<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        abort_unless($user && $user->role !== 'customer' && $user->hasPermission('crm.access'), 403);

        $routeName = (string) optional($request->route())->getName();
        foreach (config('access.route_permissions', []) as $pattern => $rule) {
            if (! Str::is($pattern, $routeName)) {
                continue;
            }

            $required = is_array($rule)
                ? ($rule[$request->method()] ?? $rule['*'] ?? null)
                : $rule;

            if ($required) {
                abort_unless($user->hasPermission($required), 403);
            }
            break;
        }

        return $next($request);
    }
}
