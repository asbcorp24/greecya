<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trainers') && ! Schema::hasColumn('trainers', 'slug')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('name');
                $table->unique('slug');
            });

            DB::table('trainers')
                ->orderBy('id')
                ->get(['id', 'name'])
                ->each(function ($trainer) {
                    $base = Str::slug($trainer->name) ?: 'trainer-'.$trainer->id;
                    $slug = $base;
                    $suffix = 2;

                    while (DB::table('trainers')
                        ->where('slug', $slug)
                        ->where('id', '!=', $trainer->id)
                        ->exists()) {
                        $slug = $base.'-'.$suffix++;
                    }

                    DB::table('trainers')
                        ->where('id', $trainer->id)
                        ->update(['slug' => $slug]);
                });
        }

        if (! Schema::hasTable('trainer_photos')) {
            Schema::create('trainer_photos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trainer_id')->constrained('trainers')->cascadeOnDelete();
                $table->string('image_path');
                $table->string('caption', 500)->nullable();
                $table->unsignedSmallInteger('sort_order')->default(100);
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();

                $table->index(['trainer_id', 'sort_order']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_photos');

        if (Schema::hasTable('trainers') && Schema::hasColumn('trainers', 'slug')) {
            Schema::table('trainers', function (Blueprint $table) {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });
        }
    }
};
