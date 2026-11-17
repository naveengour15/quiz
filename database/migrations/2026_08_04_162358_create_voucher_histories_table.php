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
        Schema::create('voucher_histories', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->unsignedBigInteger('voucher_id')->nullable()->index();

            $table->enum('voucher_type', [
                'point', 'coupon', 'gift', 'cashback', 'voucher'
            ])->default('point')->index();

            $table->integer('voucher_point')->nullable();
            $table->string('code')->nullable();

            $table->unsignedBigInteger('assign_to_user_id')->nullable()->index();
            $table->dateTime('assign_date')->nullable();

            $table->dateTime('expire_date')->nullable();
            $table->dateTime('used_date')->nullable();

            $table->unsignedBigInteger('used_user_id')->nullable()->index();

            $table->enum('status', [
                'pending',
                'assigned_to_quiz',
                'assigned_to_user',
                'used','expired'
            ])->default('pending')->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voucher_histories');
    }
};