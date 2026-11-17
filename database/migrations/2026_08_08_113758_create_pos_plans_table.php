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
        Schema::create('pos_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('plan_name');
            $table->double('price')->default(0.0);
            $table->double('offer_price')->default(0.0);

            $table->integer('validity_days')->default(0);
            $table->integer('extra_days')->default(0);

            $table->integer('sms')->default(0);
            $table->integer('whatsapp')->default(0);
            $table->integer('email')->default(0);

            $table->enum('view_for', ['retailer', 'superadmin'])->default('retailer');

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->string('create_by');
            $table->string('update_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_plans');
    }
};
