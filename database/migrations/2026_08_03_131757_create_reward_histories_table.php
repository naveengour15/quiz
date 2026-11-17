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
        Schema::create('reward_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('reward_id')->nullable();

            $table->enum('reward_type', ['point', 'coupon', 'gift', 'cashback'])
                ->default('point');

            $table->string('code')->nullable();

            $table->unsignedBigInteger('assign_to_user_id')->nullable();

            $table->dateTime('assign_date')->nullable();
            $table->dateTime('expire_date')->nullable();
            $table->dateTime('use_date')->nullable();

            $table->unsignedBigInteger('used_user_id')->nullable();

            $table->integer('deduct_point')->default(0);

            $table->enum('status', [
                'pending',
                'assigned',
                'used',
                'expired',
            ])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('reward_id');
            $table->index('assign_to_user_id');
            $table->index('used_user_id');
            $table->index('status');
            $table->index('reward_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_histories');
    }
};