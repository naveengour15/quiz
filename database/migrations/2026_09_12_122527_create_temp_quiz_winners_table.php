<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_quiz_winners', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('user_id');

            $table->integer('total_question')->default(0);
            $table->integer('total_correct_answer')->default(0);

            $table->decimal('total_time', 10, 3)->default(0.000);
            $table->decimal('per_ans_average_time', 10, 3)->default(0.000);

            $table->integer('rank')->nullable();

            $table->string('level', 191)->nullable();
            $table->string('reward_type', 191)->nullable();

            $table->unsignedBigInteger('reward_id')->nullable();

            $table->string('reward', 191)->nullable();

            $table->unsignedBigInteger('voucher_history_id')->nullable();

            $table->text('reward_detail')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();
            $table->softDeletes();

            // Unique Keys
            $table->unique(
                ['quiz_id', 'user_id'],
                'temp_quiz_winners_quiz_user_unique'
            );

            $table->unique(
                ['tenant_id', 'quiz_id', 'user_id'],
                'temp_quiz_winners_tenant_quiz_user_unique'
            );

            // Indexes
            $table->index('tenant_id');
            $table->index('quiz_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_quiz_winners');
    }
};