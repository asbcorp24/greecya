<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'online_booking')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('online_booking')->default(true)->after('requires_trainer')->index();
            });
        }

        if (! Schema::hasTable('permissions') || ! Schema::hasTable('role_permissions')) {
            return;
        }

        $permissions = [
            'services.view' => ['Просмотр услуг комплекса', 'Услуги', 235],
            'services.manage' => ['Создание и изменение услуг комплекса', 'Услуги', 236],
        ];

        foreach ($permissions as $code => [$name, $group, $sortOrder]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'name' => $name,
                    'group' => $group,
                    'sort_order' => $sortOrder,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        foreach (['admin', 'director', 'manager'] as $role) {
            foreach (array_keys($permissions) as $code) {
                $permissionId = DB::table('permissions')->where('code', $code)->value('id');
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

    public function down(): void
    {
        if (Schema::hasTable('permissions') && Schema::hasTable('role_permissions')) {
            $ids = DB::table('permissions')->whereIn('code', ['services.view', 'services.manage'])->pluck('id');
            if ($ids->isNotEmpty()) {
                DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
                DB::table('permissions')->whereIn('id', $ids)->delete();
            }
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'online_booking')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('online_booking');
            });
        }
    }
};
