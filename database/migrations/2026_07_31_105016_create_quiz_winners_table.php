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
        Schema::create('quiz_winners', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');

            $table->integer('total_question')->default(0);
            $table->integer('total_correct_answer')->default(0);

            $table->integer('total_time')->default(0)->comment('Time in seconds');
            $table->integer('per_ans_average_time')->default(0)->comment('Average time per answer in seconds');

            $table->integer('rank')->nullable();
            $table->string('level')->nullable();

            $table->string('reward_type')->nullable();
            $table->unsignedBigInteger('reward_id')->nullable();
            $table->text('reward_detail')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('quiz_id');
            $table->index('user_id');
            $table->index('status');

            // Optional Foreign Keys
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_winners');
    }
};