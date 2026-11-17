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
        Schema::create('billings', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('contact')->nullable();
            $table->unsignedBigInteger('payment_table_id')->nullable();

            $table->unsignedBigInteger('plan_id');
            $table->string('plan_name');
            $table->string('plan_for')->nullable();
            $table->string('plan_duration');

            $table->decimal('plan_amount', 10, 2);
            $table->decimal('offer_amount', 10, 2);
            $table->decimal('addon_service_amount', 8, 2)->default(0.00);

            $table->json('addon_service')->nullable();
            $table->string('free_notification')->nullable();

            $table->decimal('payable_amount', 10, 2);
            $table->decimal('tax', 10, 2);

            $table->date('plan_start_date');
            $table->date('plan_end_date');

            $table->string('invoice_path')->nullable();
            $table->string('payment_method')->nullable();

            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');

            $table->string('pay_to')->nullable();

            $table->integer('sms')->default(0);
            $table->enum('sms_status', ['active', 'inactive'])->default('inactive');

            $table->integer('email')->default(0);
            $table->enum('email_status', ['active', 'inactive'])->default('inactive');

            $table->integer('whatsapp')->default(0);
            $table->enum('whatsapp_status', ['active', 'inactive'])->default('inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billings');
    }
};
