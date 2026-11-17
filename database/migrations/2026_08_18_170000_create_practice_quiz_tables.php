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
        if (!Schema::hasTable('practice_quiz_users')) {
            Schema::create('practice_quiz_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('quiz_id');
                $table->unsignedBigInteger('user_id');
                $table->string('status')->default('active');
                $table->boolean('fifty_fifty')->default(false);
                $table->boolean('switch_option')->default(false);
                $table->unsignedBigInteger('question_id')->nullable();
                $table->integer('total_question')->default(0);
                $table->integer('total_correct_answer')->default(0);
                $table->integer('total_time')->default(0);
                $table->integer('per_ans_average_time')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['quiz_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('practice_quiz_user_answers')) {
            Schema::create('practice_quiz_user_answers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1);
                $table->unsignedBigInteger('quiz_id');
                $table->unsignedBigInteger('question_id');
                $table->unsignedBigInteger('user_id');
                $table->string('answer')->nullable();
                $table->tinyInteger('status')->default(0);
                $table->timestamp('show_time')->nullable();
                $table->timestamp('ans_time')->nullable();
                $table->integer('total_time')->default(0);
                $table->timestamps();

                $table->index(['quiz_id', 'user_id']);
                $table->index('question_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_quiz_user_answers');
        Schema::dropIfExists('practice_quiz_users');
    }
};
