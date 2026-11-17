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
        Schema::create('plan_payments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('contact')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('plan_id')->nullable();

            $table->enum('payment_type', [
                'card',
                'cash',
                'upi',
                'bank',
                'NA'
            ])->default('NA');

            $table->decimal('payment_amount', 10, 2)->nullable();

            $table->string('payment_id')->nullable();
            $table->string('signature')->nullable();

            $table->enum('status', [
                'pending',
                'success',
                'failed'
            ])->default('pending');

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('plan_id');
            $table->index('payment_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_payments');
    }
};
