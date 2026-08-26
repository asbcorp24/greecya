<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        DB::table('permissions')->updateOrInsert(
            ['code' => 'sales.pos'],
            [
                'name' => 'Первичная продажа на ресепшене и в POS',
                'group' => 'Продажи',
                'sort_order' => 245,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $permissionId = DB::table('permissions')->where('code', 'sales.pos')->value('id');
        if (! $permissionId) {
            return;
        }

        foreach (['admin', 'director', 'manager', 'cashier', 'receptionist'] as $role) {
            DB::table('role_permissions')->updateOrInsert(
                ['role' => $role, 'permission_id' => $permissionId],
                ['updated_at' => now(), 'created_at' => now()]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissionId = DB::table('permissions')->where('code', 'sales.pos')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
