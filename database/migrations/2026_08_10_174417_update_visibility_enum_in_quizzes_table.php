<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE quizzes
            MODIFY visibility
            ENUM('public', 'private', 'leaderboard', 'practice', 'mahaquiz')
            NOT NULL DEFAULT 'public'
        ");
    }

    public function down(): void
    {
        DB::table('quizzes')
            ->whereIn('visibility', ['leaderboard', 'practice', 'mahaquiz'])
            ->update(['visibility' => 'public']);

        DB::statement("
            ALTER TABLE quizzes
            MODIFY visibility
            ENUM('public', 'private')
            NOT NULL DEFAULT 'public'
        ");
    }
};