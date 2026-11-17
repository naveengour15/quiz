<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('category');
            $table->string('partner')->nullable();
            $table->unsignedInteger('coin_cost')->default(0);
            $table->decimal('cash_value', 10, 2)->default(0);
            $table->unsignedInteger('stock')->nullable();
            $table->enum('fulfillment_type', ['instant_api', 'manual_approval'])->default('manual_approval');
            $table->enum('status', ['active', 'inactive', 'out_of_stock'])->default('active');
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('redemptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained('rewards')->cascadeOnDelete();
            $table->unsignedInteger('coins')->default(0);
            $table->decimal('fraud_score', 4, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'fulfilled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('redemptions');
        Schema::dropIfExists('rewards');
    }
};
