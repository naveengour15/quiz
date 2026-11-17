<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_question_user_answers', function (Blueprint $table) {
            $table->unique(
                [
                    'tenant_id',
                    'quiz_id',
                    'question_id',
                    'user_id',
                ],
                'uq_quiz_question_user_answer'
            );
        });
    }

    public function down(): void
    {
        Schema::table('quiz_question_user_answers', function (Blueprint $table) {
            $table->dropUnique('uq_quiz_question_user_answer');
        });
    }
};
