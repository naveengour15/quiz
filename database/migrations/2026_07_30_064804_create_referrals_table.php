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
        Schema::create('referrals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');              // Referrer
            $table->unsignedBigInteger('referred_user_id')->nullable(); // New registered user
            $table->string('referral_code')->unique();
            $table->enum('status', ['pending', 'completed', 'expired'])->default('pending');
            $table->decimal('reward_amount', 12, 2)->default(0);
            $table->boolean('reward_given')->default(false);
            $table->decimal('referred_reward_amount', 12, 2)->default(0);
            $table->boolean('referred_reward_given')->default(false);
            $table->timestamp('reward_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('user_id');
            $table->index('referred_user_id');

            // Optional Foreign Keys
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            // $table->foreign('referred_user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('referral_code')->after('profile_photo')->nullable()->unique();
            $table->string('create_quiz_limit')->after('referral_code')->nullable()->unique();
            $table->string('available_quiz_limit')->after('create_quiz_limit')->nullable()->unique();
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['referral_code', 'create_quiz_limit', 'available_quiz_limit']);
        });
    }
};