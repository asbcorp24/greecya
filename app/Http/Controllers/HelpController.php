<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roles = config('help.roles', []);
        $common = config('help.common', []);

        $canBrowseAll = in_array($user->role, ['admin', 'director', 'manager'], true);
        $selectedRole = $canBrowseAll
            ? (string) $request->query('role', $user->role)
            : (string) $user->role;

        abort_unless(isset($roles[$selectedRole]), 404);

        if ($user->role === 'customer') {
            $layout = 'layouts.app';
        } elseif (in_array($user->role, ['receptionist', 'cashier', 'trainer'], true)) {
            $layout = 'workspace';
        } else {
            $layout = 'admin.layout';
        }

        return view('help.index', [
            'layout' => $layout,
            'common' => $common,
            'roles' => $roles,
            'roleHelp' => $roles[$selectedRole],
            'selectedRole' => $selectedRole,
            'canBrowseAll' => $canBrowseAll,
            'canOpenRoleLinks' => $selectedRole === $user->role || in_array($user->role, ['admin', 'director'], true),
        ]);
    }
}
