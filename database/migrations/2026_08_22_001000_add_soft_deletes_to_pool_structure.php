<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pool_zones', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by_user_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
        });

        Schema::table('pool_lanes', function (Blueprint $table) {
            $table->softDeletes();
            $table->foreignId('deleted_by_user_id')->nullable()->after('is_active')->constrained('users')->nullOnDelete();
            $table->boolean('deleted_with_zone')->default(false)->after('deleted_by_user_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('pool_lanes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by_user_id');
            $table->dropColumn('deleted_with_zone');
            $table->dropSoftDeletes();
        });

        Schema::table('pool_zones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by_user_id');
            $table->dropSoftDeletes();
        });
    }
};
