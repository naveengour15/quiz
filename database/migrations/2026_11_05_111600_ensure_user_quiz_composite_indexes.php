<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            Schema::hasTable('users')
            && Schema::hasColumn('users', 'session_token')
            && ! Schema::hasIndex('users', 'idx_users_session_token')
        ) {
            Schema::table('users', function (Blueprint $table) {
                $table->index('session_token', 'idx_users_session_token');
            });
        }

        if (
            Schema::hasTable('quiz_users')
            && Schema::hasColumn('quiz_users', 'quiz_id')
            && Schema::hasColumn('quiz_users', 'user_id')
            && ! Schema::hasIndex('quiz_users', 'idx_quiz_users_quiz_id_user_id')
        ) {
            Schema::table('quiz_users', function (Blueprint $table) {
                $table->index(['quiz_id', 'user_id'], 'idx_quiz_users_quiz_id_user_id');
            });
        }

        try {
            if (
                Schema::hasTable('quiz_question_user_answers')
                && Schema::hasColumn('quiz_question_user_answers', 'quiz_id')
                && Schema::hasColumn('quiz_question_user_answers', 'user_id')
                && Schema::hasColumn('quiz_question_user_answers', 'question_id')
                && ! Schema::hasIndex(
                    'quiz_question_user_answers',
                    'uniq_qqua_quiz_user_question',
                    'unique'
                )
            ) {
                Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                    $table->unique(
                        ['quiz_id', 'user_id', 'question_id'],
                        'uniq_qqua_quiz_user_question'
                    );
                });
            }
        } catch (\Throwable $e) {
            try {
                if (! Schema::hasIndex('quiz_question_user_answers', 'idx_qqua_quiz_user_question')) {
                    Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                        $table->index(['quiz_id', 'user_id', 'question_id'], 'idx_qqua_quiz_user_question');
                    });
                }
            } catch (\Throwable $ex) {}
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_question_user_answers')) {
            try {
                if (Schema::hasIndex('quiz_question_user_answers', 'uniq_qqua_quiz_user_question', 'unique')) {
                    Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                        $table->dropUnique('uniq_qqua_quiz_user_question');
                    });
                }
                if (Schema::hasIndex('quiz_question_user_answers', 'idx_qqua_quiz_user_question')) {
                    Schema::table('quiz_question_user_answers', function (Blueprint $table) {
                        $table->dropIndex('idx_qqua_quiz_user_question');
                    });
                }
            } catch (\Throwable $e) {}
        }

        if (Schema::hasTable('quiz_users') && Schema::hasIndex('quiz_users', 'idx_quiz_users_quiz_id_user_id')) {
            Schema::table('quiz_users', function (Blueprint $table) {
                $table->dropIndex('idx_quiz_users_quiz_id_user_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasIndex('users', 'idx_users_session_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropIndex('idx_users_session_token');
            });
        }
    }
};
