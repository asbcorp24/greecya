<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('trainer_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
        });

        Schema::table('medical_clearances', function (Blueprint $table) {
            $table->string('doctor_name')->nullable()->after('type');
            $table->string('organization')->nullable()->after('doctor_name');
            $table->text('restrictions')->nullable()->after('status');
            $table->text('contraindications')->nullable()->after('restrictions');
            $table->boolean('access_blocked')->default(false)->after('contraindications')->index();
            $table->string('blocked_reason')->nullable()->after('access_blocked');
            $table->foreignId('verified_by')->nullable()->after('blocked_reason')->constrained('users')->nullOnDelete();
            $table->dateTime('verified_at')->nullable()->after('verified_by');
        });

        Schema::create('medical_clearance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medical_clearance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status')->index();
            $table->boolean('access_blocked')->default(false);
            $table->string('reason')->nullable();
            $table->dateTime('changed_at')->index();
            $table->timestamps();
        });

        Schema::create('pool_norms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('temperature_min',5,2)->nullable();
            $table->decimal('temperature_max',5,2)->nullable();
            $table->decimal('ph_min',4,2)->nullable();
            $table->decimal('ph_max',4,2)->nullable();
            $table->decimal('free_chlorine_min',6,3)->nullable();
            $table->decimal('free_chlorine_max',6,3)->nullable();
            $table->decimal('redox_min',8,2)->nullable();
            $table->decimal('redox_max',8,2)->nullable();
            $table->decimal('turbidity_max',8,3)->nullable();
            $table->boolean('alerts_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('pool_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pool_water_log_id')->nullable()->constrained()->nullOnDelete();
            $table->string('parameter')->index();
            $table->string('severity')->default('warning')->index();
            $table->decimal('actual_value',12,3)->nullable();
            $table->string('expected_range')->nullable();
            $table->string('status')->default('open')->index();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('acknowledged_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('pool_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_lane_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->index();
            $table->dateTime('performed_at')->index();
            $table->decimal('duration_minutes',8,2)->nullable();
            $table->text('details')->nullable();
            $table->string('result')->nullable();
            $table->timestamps();
        });

        Schema::create('technical_checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('daily')->index();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('technical_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_checklist_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });

        Schema::create('technical_checklist_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_checklist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('performed_at')->index();
            $table->string('status')->default('completed')->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('technical_checklist_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_checklist_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technical_checklist_item_id')->constrained()->cascadeOnDelete();
            $table->string('result')->default('ok')->index();
            $table->text('comment')->nullable();
            $table->timestamps();
            $table->unique(['technical_checklist_run_id','technical_checklist_item_id'],'technical_check_result_unique');
        });

        Schema::create('safety_incidents', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->string('type')->index();
            $table->string('severity')->default('medium')->index();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_zone_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pool_lane_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('occurred_at')->index();
            $table->text('description');
            $table->text('actions_taken')->nullable();
            $table->boolean('ambulance_called')->default(false);
            $table->boolean('lane_closed')->default(false);
            $table->string('photo_path')->nullable();
            $table->string('status')->default('open')->index();
            $table->text('resolution')->nullable();
            $table->dateTime('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->cascadeOnDelete();
            $table->string('batch_number')->index();
            $table->string('supplier')->nullable();
            $table->date('manufactured_on')->nullable();
            $table->date('expires_on')->nullable()->index();
            $table->decimal('received_qty',12,3)->default(0);
            $table->decimal('remaining_qty',12,3)->default(0)->index();
            $table->decimal('unit_cost',12,2)->default(0);
            $table->dateTime('received_at')->index();
            $table->string('document_number')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['inventory_item_id','batch_number']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->foreignId('inventory_batch_id')->nullable()->after('inventory_item_id')->constrained()->nullOnDelete();
            $table->foreignId('pool_zone_id')->nullable()->after('order_id')->constrained()->nullOnDelete();
        });

        Schema::create('chemical_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pool_zone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('inventory_batch_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity',12,3);
            $table->string('unit')->default('кг');
            $table->dateTime('used_at')->index();
            $table->string('purpose')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chemical_usages');
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pool_zone_id');
            $table->dropConstrainedForeignId('inventory_batch_id');
        });
        Schema::dropIfExists('inventory_batches');
        Schema::dropIfExists('safety_incidents');
        Schema::dropIfExists('technical_checklist_results');
        Schema::dropIfExists('technical_checklist_runs');
        Schema::dropIfExists('technical_checklist_items');
        Schema::dropIfExists('technical_checklists');
        Schema::dropIfExists('pool_operations');
        Schema::dropIfExists('pool_alerts');
        Schema::dropIfExists('pool_norms');
        Schema::dropIfExists('medical_clearance_history');
        Schema::table('medical_clearances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['doctor_name','organization','restrictions','contraindications','access_blocked','blocked_reason','verified_at']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trainer_id');
        });
    }
};
