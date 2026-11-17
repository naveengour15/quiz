<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('quiz_question_user_answers')) {
            // Drop redundant duplicate unique index 'uq_quiz_question_user_answer'
            // The primary unique key remains 'quiz_question_user_answers_tenant_quiz_user_question_unique'
            try {
                $indexes = collect(DB::select("SHOW INDEX FROM quiz_question_user_answers"))->pluck('Key_name')->all();
                if (in_array('uq_quiz_question_user_answer', $indexes)) {
                    Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                        $table->dropUnique('uq_quiz_question_user_answer');
                    });
                }
            } catch (\Throwable $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_question_user_answers')) {
            try {
                Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                    $table->unique(
                        ['tenant_id', 'quiz_id', 'question_id', 'user_id'],
                        'uq_quiz_question_user_answer'
                    );
                });
            } catch (\Throwable $e) {}
        }
    }
};
