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
        Schema::create('survey_rewards', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('survey_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('reward_type')->nullable();
            $table->decimal('point', 10, 2)->default(0);
            $table->string('quiz')->nullable();

            $table->enum('status', ['pending', 'settled'])->default('pending');

            $table->timestamps();
            $table->softDeletes();

            // Optional Foreign Keys
            // $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_rewards');
    }
};