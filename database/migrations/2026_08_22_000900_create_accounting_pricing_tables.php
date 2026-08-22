<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('accounting_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('1С:Бухгалтерия');
            $table->string('driver')->default('json_http')->index();
            $table->string('endpoint_url')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->text('token')->nullable();
            $table->string('organization_code')->nullable();
            $table->string('format_version')->default('1.23');
            $table->json('options')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->dateTime('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_integration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('direction')->default('export')->index();
            $table->string('format')->default('json')->index();
            $table->string('status')->default('running')->index();
            $table->dateTime('period_from')->nullable();
            $table->dateTime('period_to')->nullable();
            $table->json('record_counts')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->text('error_text')->nullable();
            $table->dateTime('started_at')->index();
            $table->dateTime('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('accounting_external_refs', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type')->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('external_id')->index();
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['entity_type', 'entity_id']);
        });

        Schema::create('pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('target_type')->default('service')->index();
            $table->foreignId('service_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('customer_segment')->default('all')->index();
            $table->json('weekdays')->nullable();
            $table->time('time_from')->nullable();
            $table->time('time_to')->nullable();
            $table->decimal('occupancy_min_pct', 5, 2)->nullable();
            $table->decimal('occupancy_max_pct', 5, 2)->nullable();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('adjustment_type')->default('percent')->index();
            $table->decimal('adjustment_value', 12, 2)->default(0);
            $table->decimal('min_price', 12, 2)->nullable();
            $table->decimal('max_price', 12, 2)->nullable();
            $table->unsignedSmallInteger('priority')->default(100)->index();
            $table->boolean('combinable')->default(true);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('base_total', 12, 2)->nullable()->after('total');
            $table->json('pricing_meta')->nullable()->after('base_total');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('base_price', 12, 2)->nullable()->after('price');
            $table->json('pricing_meta')->nullable()->after('base_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['base_price', 'pricing_meta']);
        });
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['base_total', 'pricing_meta']);
        });
        Schema::dropIfExists('pricing_rules');
        Schema::dropIfExists('accounting_external_refs');
        Schema::dropIfExists('accounting_sync_runs');
        Schema::dropIfExists('accounting_integrations');
    }
};
