<?php

// No-op migration: unique index for quiz_question_user_answers is already handled by
// database/migrations/2026_08_22_203120_add_unique_quiz_question_user_to_quiz_question_user_answers_table.php

use Illuminate\Database\Migrations\Migration;

class AddUniqueIndexToQuizQuestionUserAnswers extends Migration
{
    public function up(): void
    {
        // intentionally left blank
    }

    public function down(): void
    {
        // intentionally left blank
    }
}
