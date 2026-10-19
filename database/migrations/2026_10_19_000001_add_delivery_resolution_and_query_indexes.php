<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->timestampTz('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->text('resolution_note')->nullable();
            $table->index(['status','updated_at']);
            $table->index(['voucher_id','status','updated_at']);
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->timestampTz('customer_confirmed_at')->nullable();
            $table->index('merchant_ref');
            $table->index(['status','customer_confirmed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('deliveries', function (Blueprint $table) {
            $table->dropIndex(['status','updated_at']);
            $table->dropIndex(['voucher_id','status','updated_at']);
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropColumn(['resolved_at','resolution_note']);
        });
        Schema::table('payment_orders', function (Blueprint $table) {
            $table->dropIndex(['status','customer_confirmed_at']);
            $table->dropIndex(['merchant_ref']);
            $table->dropColumn('customer_confirmed_at');
        });
    }
};
