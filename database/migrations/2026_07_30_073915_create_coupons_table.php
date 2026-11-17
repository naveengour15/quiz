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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');

            $table->string('code')->unique();

            $table->enum('discount_type', ['fixed', 'percentage']);
            $table->decimal('discount_value', 10, 2);

            $table->decimal('min_purchase', 10, 2)->default(0);

            $table->unsignedInteger('usage_limit_total')->default(0);
            $table->unsignedInteger('usage_limit_per_user')->default(1);
            $table->unsignedInteger('used_count')->default(0);

            $table->json('applicable_plans')->nullable();

            $table->date('expiry_date')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();

            $table->index('tenant_id');
            $table->index('status');
            $table->index('expiry_date');

            // Optional foreign key
            // $table->foreign('tenant_id')
            //     ->references('id')
            //     ->on('tenants')
            //     ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
