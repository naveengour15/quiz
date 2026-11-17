<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {

            $table->text('background_sound')
                ->nullable()
                ->after('retailer_referral_limit');

            $table->text('question_appear_sound')
                ->nullable()
                ->after('background_sound');

            $table->text('start_quiz_sound')
                ->nullable()
                ->after('question_appear_sound');

            $table->text('answer_appear_sound')
                ->nullable()
                ->after('start_quiz_sound');

            $table->text('quiz_end_sound')
                ->nullable()
                ->after('answer_appear_sound');

            $table->text('answer_sheet_appear_sound')
                ->nullable()
                ->after('quiz_end_sound');

            $table->text('leaderboard_appear_sound')
                ->nullable()
                ->after('answer_sheet_appear_sound');

            $table->text('counter_sound')
                ->nullable()
                ->after('leaderboard_appear_sound');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {

            $table->dropColumn([
                'background_sound',
                'question_appear_sound',
                'start_quiz_sound',
                'answer_appear_sound',
                'quiz_end_sound',
                'answer_sheet_appear_sound',
                'leaderboard_appear_sound',
                'counter_sound',
            ]);
        });
    }
};