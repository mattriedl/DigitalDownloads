<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments: gateways (Stripe, PayPal, crypto — installed switched off), membership plans,
 * subscriptions and payments. Nothing here is used until memberships are turned on.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique(); // stripe, paypal, nowpayments
            $table->string('name');
            $table->boolean('is_enabled')->default(false);
            $table->string('mode', 10)->default('test'); // test, live
            $table->text('credentials')->nullable(); // encrypted JSON
            $table->json('currencies')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('interval', 10)->default('month'); // month, year
            $table->unsignedSmallInteger('trial_days')->default(0);
            $table->json('perks')->nullable(); // no_ads, no_countdown, priority, members_only
            $table->json('gateway_ids')->nullable(); // per-gateway price/product ids
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 30);
            $table->string('gateway_subscription_id')->nullable()->index();
            $table->string('status', 20)->default('pending'); // pending, trialing, active, past_due, canceled, expired
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 30);
            $table->string('gateway_reference')->nullable()->index();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending'); // pending, paid, failed, refunded
            $table->json('meta')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('payment_gateways');
    }
};
