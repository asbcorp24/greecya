<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pool_zones', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('pool')->index();
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('pool_lanes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('number');
            $table->decimal('length_meters', 6, 2)->default(25);
            $table->unsignedSmallInteger('capacity')->default(6);
            $table->string('status')->default('open')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['pool_zone_id', 'number']);
        });

        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->foreignId('pool_zone_id')->nullable()->after('trainer_id')->constrained()->nullOnDelete();
            $table->string('session_type')->default('free_swim')->after('pool_zone_id')->index();
            $table->boolean('online_booking')->default(true)->after('status');
            $table->unsignedSmallInteger('waitlist_capacity')->default(10)->after('online_booking');
        });

        Schema::create('schedule_slot_lane', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pool_lane_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->timestamps();
            $table->unique(['schedule_slot_id', 'pool_lane_id']);
        });

        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('type')->default('membership')->index();
            $table->unsignedSmallInteger('duration_days')->default(30);
            $table->unsignedSmallInteger('visits_included')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('freeze_days')->default(0);
            $table->unsignedSmallInteger('guest_visits')->default(0);
            $table->time('access_from')->nullable();
            $table->time('access_to')->nullable();
            $table->json('allowed_weekdays')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('active')->index();
            $table->date('starts_on')->index();
            $table->date('ends_on')->index();
            $table->unsignedSmallInteger('visits_total')->nullable();
            $table->unsignedSmallInteger('visits_used')->default(0);
            $table->unsignedSmallInteger('freeze_days_total')->default(0);
            $table->unsignedSmallInteger('freeze_days_used')->default(0);
            $table->unsignedSmallInteger('guest_visits_left')->default(0);
            $table->boolean('auto_renew')->default(false);
            $table->decimal('price_paid', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('membership_id')->nullable()->after('trainer_id')->constrained()->nullOnDelete();
        });

        Schema::create('membership_freezes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->unsignedSmallInteger('days');
            $table->string('reason')->nullable();
            $table->string('status')->default('approved')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('deposit_balance', 12, 2)->default(0);
            $table->decimal('bonus_balance', 12, 2)->default(0);
            $table->string('loyalty_level')->default('base')->index();
            $table->timestamps();
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_wallet_id')->constrained()->cascadeOnDelete();
            $table->string('wallet_type')->default('deposit')->index();
            $table->string('direction')->index();
            $table->decimal('amount', 12, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('access_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('type')->default('qr')->index();
            $table->string('status')->default('active')->index();
            $table->dateTime('issued_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('access_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('access_card_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('visit_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type')->default('enter')->index();
            $table->string('result')->default('allowed')->index();
            $table->string('reason')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('lockers', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('zone')->default('general')->index();
            $table->string('gender')->nullable()->index();
            $table->string('status')->default('available')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('locker_rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('locker_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('started_at')->index();
            $table->dateTime('ends_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->string('status')->default('active')->index();
            $table->decimal('deposit', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('medical_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('pool')->index();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->string('document_path')->nullable();
            $table->string('status')->default('valid')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('people')->default(1);
            $table->unsignedSmallInteger('priority')->default(100);
            $table->string('status')->default('waiting')->index();
            $table->dateTime('notified_at')->nullable();
            $table->timestamps();
            $table->unique(['schedule_slot_id', 'customer_id']);
        });

        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('call')->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable()->index();
            $table->string('status')->default('open')->index();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->default('phone')->index();
            $table->string('direction')->default('out')->index();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->string('type')->default('work')->index();
            $table->string('status')->default('planned')->index();
            $table->unsignedInteger('worked_minutes')->default(0);
            $table->timestamps();
        });

        Schema::create('payroll_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('calc_type')->default('session')->index();
            $table->decimal('rate', 12, 2)->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('payroll_accruals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('trainer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payroll_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_month')->index();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->string('status')->default('accrued')->index();
            $table->dateTime('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->restrictOnDelete();
            $table->foreignId('opened_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('opened_at')->index();
            $table->dateTime('closed_at')->nullable();
            $table->decimal('opening_cash', 12, 2)->default(0);
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->string('status')->default('open')->index();
            $table->timestamps();
        });

        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_shift_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('sale')->index();
            $table->string('method')->default('cash')->index();
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category')->default('retail')->index();
            $table->string('unit')->default('шт');
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->boolean('track_marking')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->dateTime('occurred_at')->index();
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::create('corporate_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('tax_id')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('corporate_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('employee_number')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['corporate_account_id', 'customer_id']);
        });

        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('membership_contract')->index();
            $table->longText('body');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('customer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('contract')->index();
            $table->string('number')->unique();
            $table->string('status')->default('draft')->index();
            $table->string('sign_method')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->longText('content')->nullable();
            $table->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('channel')->default('email')->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('audience')->nullable();
            $table->string('status')->default('draft')->index();
            $table->dateTime('scheduled_at')->nullable()->index();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel')->index();
            $table->string('recipient');
            $table->string('status')->default('queued')->index();
            $table->text('body')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pool_water_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('measured_at')->index();
            $table->decimal('temperature', 5, 2)->nullable();
            $table->decimal('ph', 4, 2)->nullable();
            $table->decimal('free_chlorine', 6, 3)->nullable();
            $table->decimal('redox', 8, 2)->nullable();
            $table->decimal('turbidity', 8, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('maintenance_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_lane_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('type')->default('maintenance')->index();
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('completed_at')->nullable();
            $table->string('status')->default('open')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('photo_path')->nullable();
            $table->string('gender')->nullable()->index();
            $table->string('emergency_contact')->nullable();
            $table->boolean('marketing_consent')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'gender', 'emergency_contact', 'marketing_consent']);
        });
        Schema::dropIfExists('maintenance_tasks');
        Schema::dropIfExists('pool_water_logs');
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('customer_documents');
        Schema::dropIfExists('document_templates');
        Schema::dropIfExists('corporate_members');
        Schema::dropIfExists('corporate_accounts');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_shifts');
        Schema::dropIfExists('cash_registers');
        Schema::dropIfExists('payroll_accruals');
        Schema::dropIfExists('payroll_rules');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('customer_interactions');
        Schema::dropIfExists('crm_tasks');
        Schema::dropIfExists('waitlist_entries');
        Schema::dropIfExists('medical_clearances');
        Schema::dropIfExists('locker_rentals');
        Schema::dropIfExists('lockers');
        Schema::dropIfExists('access_events');
        Schema::dropIfExists('access_cards');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('customer_wallets');
        Schema::dropIfExists('membership_freezes');
        Schema::table('bookings', function (Blueprint $table) { $table->dropConstrainedForeignId('membership_id'); });
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
        Schema::dropIfExists('schedule_slot_lane');
        Schema::table('schedule_slots', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pool_zone_id');
            $table->dropColumn(['session_type', 'online_booking', 'waitlist_capacity']);
        });
        Schema::dropIfExists('pool_lanes');
        Schema::dropIfExists('pool_zones');
    }
};
