<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'unrestricted_booking')) {
                $table->boolean('unrestricted_booking')->default(false)->after('online_booking')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (Schema::hasColumn('services', 'unrestricted_booking')) {
                $table->dropColumn('unrestricted_booking');
            }
        });
    }
};
