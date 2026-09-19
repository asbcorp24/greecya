<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'unrestricted_booking')) {
            Schema::table('services', function (Blueprint $table) {
                $table->boolean('unrestricted_booking')->default(false)->after('online_booking')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'unrestricted_booking')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('unrestricted_booking');
            });
        }
    }
};
