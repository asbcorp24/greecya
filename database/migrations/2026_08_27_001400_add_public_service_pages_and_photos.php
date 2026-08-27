<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('services') && ! Schema::hasColumn('services', 'main_image_path')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('main_image_path')->nullable()->after('description');
            });
        }

        if (! Schema::hasTable('service_photos')) {
            Schema::create('service_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
                $table->string('image_path');
                $table->string('caption', 500)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(100);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index(['service_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_photos');

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'main_image_path')) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('main_image_path');
            });
        }
    }
};
