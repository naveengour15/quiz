<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_billings', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');

            $table->unsignedBigInteger('payment_id')->unique();
            $table->unsignedBigInteger('plan_id');
            $table->string('plan_name');

            $table->integer('validity_days')->default(0);
            $table->integer('extra_days')->default(0);
            $table->string('plan_duration')->nullable();

            $table->decimal('plan_amount', 10, 2);
            $table->decimal('offer_amount', 10, 2)->default(0);
            $table->decimal('addon_service_amount', 10, 2)->default(0);        
            $table->json('addon_service')->nullable();

            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('payable_amount', 10, 2);
            
            $table->integer('sms')->default(0);
            $table->enum('sms_status', [
                'active',
                'expired',
                'cancelled'
            ])->default('active');

            $table->integer('whatsapp')->default(0);
            $table->enum('whatsapp_status', [
                'active',
                'expired',
                'cancelled'
            ])->default('active');

            $table->integer('email')->default(0);
            $table->enum('email_status', [
                'active',
                'expired',
                'cancelled'
            ])->default('active');

            $table->date('plan_start_date')->nullable();
            $table->date('plan_end_date')->nullable();
            
            $table->enum('payment_method', [
                'cash',
                'card',
                'upi',
                'wallet',
                'net_banking',
                'paypal',
                'razorpay',
                'stripe'
            ])->nullable();

            $table->string('transaction_id')->nullable();

            $table->date('billing_date');
            $table->date('expiry_date');

            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'cancelled',
                'refunded'
            ])->default('pending');

            $table->enum('status', [
                'active',
                'expired',
                'cancelled'
            ])->default('active');

            $table->text('remarks')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('payment_status');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_billings');
    }
};