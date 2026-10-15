<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->boolean('active')->default(true));
        Schema::create('branches', function (Blueprint $t) {
            $t->id(); $t->string('name', 100); $t->string('address', 500); $t->boolean('active')->default(true); $t->timestampsTz();
        });
        Schema::create('settings', function (Blueprint $t) {
            $t->string('key')->primary(); $t->text('value')->nullable(); $t->timestampsTz();
        });
        Schema::create('qris_assets', function (Blueprint $t) {
            $t->id(); $t->string('path')->unique(); $t->string('mime', 40); $t->string('merchant_name', 150); $t->string('receiver_key', 100); $t->foreignId('uploaded_by')->constrained('users'); $t->timestampsTz();
        });
        Schema::create('voucher_policies', function (Blueprint $t) {
            $t->id(); $t->unsignedInteger('price'); $t->unsignedInteger('minimum_order');
            $t->jsonb('tiers'); $t->unsignedInteger('valid_days')->default(365); $t->timestampsTz();
        });
        Schema::create('payment_orders', function (Blueprint $t) {
            $t->id(); $t->string('reference', 24)->unique(); $t->string('token_hash', 64)->unique(); $t->text('access_token');
            $t->text('customer_name')->nullable(); $t->text('phone')->nullable(); $t->foreignId('preferred_branch_id')->nullable()->constrained('branches');
            $t->date('wedding_date')->nullable(); $t->boolean('marketing_consent'); $t->timestampTz('service_consented_at');
            $t->foreignId('policy_id')->constrained('voucher_policies'); $t->foreignId('qris_asset_id')->constrained('qris_assets');
            $t->unsignedInteger('price'); $t->string('receiver_key', 100); $t->string('status', 32)->default('MENUNGGU_PEMBAYARAN')->index();
            $t->text('customer_note')->nullable(); $t->text('correction_reason')->nullable(); $t->text('tracking')->nullable(); $t->string('campaign', 150)->nullable()->index();
            $t->string('merchant_ref', 150)->nullable(); $t->timestampTz('paid_at')->nullable()->index(); $t->timestampTz('verified_at')->nullable();
            $t->foreignId('verified_by')->nullable()->constrained('users'); $t->timestampTz('anonymized_at')->nullable(); $t->boolean('support_open')->default(false);
            $t->timestampsTz(); $t->index('created_at'); $t->unique(['receiver_key', 'merchant_ref']);
        });
        Schema::create('vouchers', function (Blueprint $t) {
            $t->id(); $t->foreignId('payment_order_id')->unique()->constrained(); $t->string('code', 12)->unique();
            $t->string('token_hash', 64)->unique(); $t->text('access_token'); $t->text('owner_name')->nullable(); $t->text('phone')->nullable();
            $t->boolean('marketing_consent'); $t->string('status', 16)->default('AKTIF')->index(); $t->unsignedInteger('revision')->default(1);
            $t->timestampTz('viewed_at')->nullable(); $t->timestampTz('scanned_at')->nullable(); $t->timestampTz('anonymized_at')->nullable(); $t->timestampsTz();
        });
        Schema::create('redemptions', function (Blueprint $t) {
            $t->id(); $t->foreignId('voucher_id')->unique()->constrained(); $t->foreignId('branch_id')->constrained();
            $t->string('order_ref', 100); $t->foreignId('actor_id')->constrained('users'); $t->uuid('request_key')->unique(); $t->string('request_digest', 64);
            $t->unsignedInteger('discount'); $t->unsignedInteger('age_days'); $t->timestampTz('claimed_at')->index(); $t->timestampsTz();
            $t->unique(['branch_id', 'order_ref']);
        });
        Schema::create('transfers', function (Blueprint $t) {
            $t->id(); $t->foreignId('voucher_id')->unique()->constrained(); $t->foreignId('actor_id')->constrained('users');
            $t->text('old_owner')->nullable(); $t->text('new_owner')->nullable(); $t->text('approval_note')->nullable(); $t->timestampsTz();
        });
        Schema::create('audit_logs', function (Blueprint $t) {
            $t->id(); $t->foreignId('actor_id')->nullable()->constrained('users'); $t->string('action', 40); $t->string('subject_type', 32);
            $t->unsignedBigInteger('subject_id'); $t->jsonb('details'); $t->timestampTz('created_at'); $t->index(['subject_type', 'subject_id']);
        });
        Schema::create('deliveries', function (Blueprint $t) {
            $t->id(); $t->foreignId('voucher_id')->constrained(); $t->string('kind', 30); $t->string('event_id', 64)->unique();
            $t->unsignedInteger('revision'); $t->text('payload')->nullable(); $t->string('status', 16)->default('PENDING')->index();
            $t->string('last_error', 200)->nullable(); $t->timestampTz('sent_at')->nullable(); $t->timestampsTz();
        });
        Schema::create('exports', function (Blueprint $t) {
            $t->id(); $t->foreignId('user_id')->constrained(); $t->jsonb('filters'); $t->string('status', 16)->default('PENDING');
            $t->string('path')->nullable(); $t->timestampTz('expires_at'); $t->timestampsTz();
        });
    }

    public function down(): void
    {
        foreach (['exports','deliveries','audit_logs','transfers','redemptions','vouchers','payment_orders','voucher_policies','qris_assets','settings','branches'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('active'));
    }
};
