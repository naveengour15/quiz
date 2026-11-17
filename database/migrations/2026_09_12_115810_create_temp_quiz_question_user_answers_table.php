<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('temp_quiz_question_user_answers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');            
            $table->enum('quiz_type', ['quiz', 'fff'])->default('quiz');
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('user_id');

            $table->text('answer')->nullable();

            $table->boolean('status')->default(false);

            $table->time('show_time')->nullable();
            $table->time('ans_time', 3)->nullable();

            $table->double('total_time')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['tenant_id', 'quiz_id', 'question_id', 'user_id'],
                'uq_temp_quiz_question_user_answer'
            );

            $table->unique(
                ['tenant_id', 'quiz_id', 'user_id', 'question_id'],
                'temp_quiz_question_user_answers_tenant_quiz_user_question_unique'
            );

            $table->index('tenant_id');
            $table->index('quiz_id');
            $table->index('question_id');
            $table->index('user_id');
            $table->index('status');

            $table->index(
                ['quiz_id', 'user_id'],
                'idx_temp_qqua_quiz_user'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_quiz_question_user_answers');
    }
};