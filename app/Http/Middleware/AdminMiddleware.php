<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role, ['admin', 'manager', 'accountant'], true), 403);

        if ($user->role === 'accountant') {
            $routeName = (string) optional($request->route())->getName();
            $allowed = [
                'admin.dashboard',
                'admin.orders.index',
                'admin.orders.update',
                'admin.customers.index',
                'admin.finance.index',
                'admin.finance.registers.store',
                'admin.finance.shifts.open',
                'admin.finance.shifts.close',
                'admin.finance.transactions.store',
                'admin.staff.index',
                'admin.staff.shifts.store',
                'admin.staff.shifts.update',
                'admin.staff.rules.store',
                'admin.staff.payroll.calculate',
                'admin.staff.payroll.pay',
                'admin.inventory.index',
                'admin.inventory.store',
                'admin.inventory.movements.store',
                'admin.reports.index',
            ];

            abort_unless(in_array($routeName, $allowed, true), 403);
        }

        return $next($request);
    }
}
