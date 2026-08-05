<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type')->default('ticket')->index();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedSmallInteger('visits_count')->nullable();
            $table->unsignedSmallInteger('validity_days')->default(30);
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('new')->index();
            $table->string('payment_status')->default('pending')->index();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('promo_code')->nullable()->index();
            $table->string('source')->default('site')->index();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->decimal('total', 10, 2);
            $table->string('ticket_code')->nullable()->unique();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('visits_left')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('provider')->default('manual')->index();
            $table->string('external_id')->nullable()->index();
            $table->string('status')->default('pending')->index();
            $table->decimal('amount', 10, 2);
            $table->json('payload')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('visited_at')->index();
            $table->unsignedSmallInteger('guests')->default(1);
            $table->string('source')->default('reception');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visits');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
    }
};
