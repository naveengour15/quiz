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
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedInteger('quiz_max_player_limit')
                  ->default(50)
                  ->after('status');

            $table->unsignedInteger('quiz_max_question_limit')
                  ->default(10)
                  ->after('quiz_max_player_limit');

            // Monthly / Plan Based Quiz Limit
            $table->unsignedInteger('quiz_limit')
                  ->default(30)
                  ->comment('Monthly or plan based quiz limit')
                  ->after('quiz_max_question_limit');

            $table->unsignedInteger('quiz_daily_play_limit')
                  ->default(2)
                  ->after('quiz_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_max_player_limit',
                'quiz_max_question_limit',
                'quiz_limit',
                'quiz_daily_play_limit',
            ]);
        });
    }
};