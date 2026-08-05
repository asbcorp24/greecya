<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category')->default('pool')->index();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('requires_trainer')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->string('phone')->nullable();
            $table->text('bio')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('status')->default('open')->index();
            $table->timestamps();
            $table->index(['service_id', 'starts_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_slots');
        Schema::dropIfExists('trainers');
        Schema::dropIfExists('services');
    }
};
