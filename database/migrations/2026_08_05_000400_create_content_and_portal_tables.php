<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trainers', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->unsignedSmallInteger('experience_years')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(100)->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->index();
        });

        Schema::create('news_posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('body');
            $table->string('image_path')->nullable();
            $table->dateTime('published_at')->nullable()->index();
            $table->boolean('is_published')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('gallery_albums', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('cover_path')->nullable();
            $table->boolean('is_published')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gallery_album_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_published')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('hero_slides', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('subtitle')->nullable();
            $table->string('image_path');
            $table->string('button_text')->nullable();
            $table->string('button_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->string('serial')->unique();
            $table->string('token', 64)->unique();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->string('sender_name')->nullable();
            $table->text('message')->nullable();
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable()->index();
            $table->dateTime('redeemed_at')->nullable();
            $table->foreignId('redeemed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('training_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('goal')->nullable();
            $table->text('description')->nullable();
            $table->text('schedule_text')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('training_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_plan_id')->constrained()->cascadeOnDelete();
            $table->string('day_label')->nullable();
            $table->string('exercise');
            $table->unsignedSmallInteger('sets')->nullable();
            $table->string('reps')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('training_progress_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on')->index();
            $table->decimal('weight', 5, 2)->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->text('note')->nullable();
            $table->text('coach_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_progress_entries');
        Schema::dropIfExists('training_plan_items');
        Schema::dropIfExists('training_plans');
        Schema::dropIfExists('certificates');
        Schema::dropIfExists('hero_slides');
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_albums');
        Schema::dropIfExists('news_posts');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('customer_id');
        });

        Schema::table('trainers', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'experience_years', 'sort_order']);
        });
    }
};
