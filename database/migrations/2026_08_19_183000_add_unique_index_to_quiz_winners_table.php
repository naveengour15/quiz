<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Clean up duplicate records keeping the row with the highest ID
        DB::statement("
            DELETE w1 FROM quiz_winners w1
            INNER JOIN quiz_winners w2 
            ON w1.quiz_id = w2.quiz_id 
            AND w1.user_id = w2.user_id 
            AND w1.id < w2.id
        ");

        // 2. Add composite unique index on (quiz_id, user_id)
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->unique(['quiz_id', 'user_id'], 'quiz_winners_quiz_user_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->dropUnique('quiz_winners_quiz_user_unique');
        });
    }
};
