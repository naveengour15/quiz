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
        // 1. quiz_users performance indexes
        if (Schema::hasTable('quiz_users')) {
            Schema::table('quiz_users', function (Blueprint $table) {
                $table->index(['quiz_id', 'status'], 'idx_quiz_users_quiz_status');
            });
        }

        // 2. quizzes schedule & status filter index
        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->index(['approve_status', 'complete_status', 'scheduled_at'], 'idx_quizzes_status_sched');
            });
        }

        // 3. quiz_notifications lookup index
        if (Schema::hasTable('quiz_notifications')) {
            Schema::table('quiz_notifications', function (Blueprint $table) {
                $table->index(['quiz_id', 'user_id'], 'idx_quiz_notif_quiz_user');
            });
        }

        // 4. quiz_question_user_answers performance index
        if (Schema::hasTable('quiz_question_user_answers')) {
            Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                $table->index(['quiz_id', 'user_id'], 'idx_qqua_quiz_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_users')) {
            Schema::table('quiz_users', function (Blueprint $table) {
                $table->dropIndex('idx_quiz_users_quiz_status');
            });
        }

        if (Schema::hasTable('quizzes')) {
            Schema::table('quizzes', function (Blueprint $table) {
                $table->dropIndex('idx_quizzes_status_sched');
            });
        }

        if (Schema::hasTable('quiz_notifications')) {
            Schema::table('quiz_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_quiz_notif_quiz_user');
            });
        }

        if (Schema::hasTable('quiz_question_user_answers')) {
            Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                $table->dropIndex('idx_qqua_quiz_user');
            });
        }
    }
};
