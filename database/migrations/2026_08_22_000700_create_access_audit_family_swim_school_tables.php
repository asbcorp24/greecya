<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('group')->index();
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role')->index();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role', 'permission_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action')->index();
            $table->string('route_name')->nullable()->index();
            $table->string('method', 10)->nullable();
            $table->string('subject_type')->nullable()->index();
            $table->unsignedBigInteger('subject_id')->nullable()->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('note')->index();
            $table->text('body');
            $table->boolean('is_private')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('active')->index();
            $table->date('target_date')->nullable();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->timestamps();
        });

        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('primary_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('family_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('relation')->default('member')->index();
            $table->boolean('is_guardian')->default(false);
            $table->boolean('can_manage_bookings')->default(false);
            $table->boolean('can_use_wallet')->default(true);
            $table->timestamps();
            $table->unique(['family_id', 'customer_id']);
        });

        Schema::create('family_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('child_customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type')->default('pool_visit')->index();
            $table->string('status')->default('signed')->index();
            $table->timestamp('signed_at')->nullable();
            $table->date('expires_on')->nullable();
            $table->string('document_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('family_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('deposit_balance', 12, 2)->default(0);
            $table->decimal('bonus_balance', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('family_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('wallet_type')->default('deposit')->index();
            $table->string('direction')->index();
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('swim_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->unsignedTinyInteger('age_min')->nullable();
            $table->unsignedTinyInteger('age_max')->nullable();
            $table->string('level')->nullable()->index();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_lane_id')->nullable()->constrained()->nullOnDelete();
            $table->date('season_start')->nullable();
            $table->date('season_end')->nullable();
            $table->unsignedSmallInteger('max_members')->default(12);
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('swim_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swim_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guardian_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->date('joined_on')->nullable();
            $table->date('left_on')->nullable();
            $table->string('status')->default('active')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['swim_group_id', 'customer_id']);
        });

        Schema::create('swim_group_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swim_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_slot_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_lane_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('status')->default('scheduled')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('swim_attendance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swim_group_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('swim_group_member_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('present')->index();
            $table->dateTime('checkin_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['swim_group_session_id', 'swim_group_member_id'], 'swim_attendance_unique');
        });

        Schema::create('swim_makeups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swim_group_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('missed_session_id')->constrained('swim_group_sessions')->cascadeOnDelete();
            $table->foreignId('makeup_session_id')->nullable()->constrained('swim_group_sessions')->nullOnDelete();
            $table->string('status')->default('available')->index();
            $table->date('expires_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('swim_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('swim_group_member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('recorded_on')->index();
            $table->string('skill');
            $table->unsignedTinyInteger('score')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('audience_type')->default('individual')->after('type')->index();
            $table->unsignedSmallInteger('weekly_visit_limit')->nullable()->after('visits_included');
            $table->json('allowed_service_ids')->nullable()->after('allowed_weekdays');
            $table->json('allowed_pool_zone_ids')->nullable()->after('allowed_service_ids');
            $table->unsignedTinyInteger('family_member_limit')->nullable()->after('allowed_pool_zone_ids');
            $table->boolean('corporate_required')->default(false)->after('family_member_limit');
            $table->foreignId('personal_trainer_id')->nullable()->after('corporate_required')->constrained('trainers')->nullOnDelete();
            $table->boolean('requires_medical_clearance')->default(true)->after('personal_trainer_id');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->foreignId('primary_holder_id')->nullable()->after('family_id')->constrained('customers')->nullOnDelete();
        });

        Schema::table('visits', function (Blueprint $table) {
            $table->foreignId('membership_id')->nullable()->after('booking_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropConstrainedForeignId('membership_id');
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_id');
            $table->dropConstrainedForeignId('primary_holder_id');
        });

        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_trainer_id');
            $table->dropColumn(['audience_type','weekly_visit_limit','allowed_service_ids','allowed_pool_zone_ids','family_member_limit','corporate_required','requires_medical_clearance']);
        });

        Schema::dropIfExists('swim_progress');
        Schema::dropIfExists('swim_makeups');
        Schema::dropIfExists('swim_attendance');
        Schema::dropIfExists('swim_group_sessions');
        Schema::dropIfExists('swim_group_members');
        Schema::dropIfExists('swim_groups');
        Schema::dropIfExists('family_wallet_transactions');
        Schema::dropIfExists('family_wallets');
        Schema::dropIfExists('family_consents');
        Schema::dropIfExists('family_members');
        Schema::dropIfExists('families');
        Schema::dropIfExists('customer_goals');
        Schema::dropIfExists('customer_notes');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
};
