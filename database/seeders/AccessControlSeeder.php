<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessControlSeeder extends Seeder
{
    public function run(): void
    {
        $sort = 10;
        foreach (config('access.permissions', []) as $code => $meta) {
            [$name, $group] = $meta;
            Permission::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'group' => $group, 'sort_order' => $sort]
            );
            $sort += 10;
        }

        $allIds = Permission::pluck('id', 'code');
        foreach (config('access.defaults', []) as $role => $codes) {
            if ($role === 'customer') {
                continue;
            }
            $resolved = in_array('*', $codes, true) ? $allIds->keys()->all() : $codes;
            foreach ($resolved as $code) {
                $permissionId = $allIds[$code] ?? null;
                if (! $permissionId) {
                    continue;
                }
                DB::table('role_permissions')->updateOrInsert(
                    ['role' => $role, 'permission_id' => $permissionId],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }
    }
}
