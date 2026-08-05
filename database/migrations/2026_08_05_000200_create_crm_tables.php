<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->unique();
            $table->string('email')->nullable()->index();
            $table->date('birth_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('source')->default('site')->index();
            $table->dateTime('last_visit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->index();
            $table->string('channel')->default('site')->index();
            $table->text('request')->nullable();
            $table->string('status')->default('new')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('follow_up_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('schedule_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('people')->default(1);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->text('comment')->nullable();
            $table->string('source')->default('site')->index();
            $table->dateTime('confirmed_at')->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('customers');
    }
};
