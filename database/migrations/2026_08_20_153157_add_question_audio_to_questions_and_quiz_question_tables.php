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
        // questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->string('question_audio')
                ->nullable()
                ->after('question_text');
        });

        // quiz_question table
        Schema::table('quiz_question', function (Blueprint $table) {
            $table->string('question_audio')
                ->nullable()
                ->after('question_text');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // questions table
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('question_audio');
        });

        // quiz_question table
        Schema::table('quiz_question', function (Blueprint $table) {
            $table->dropColumn('question_audio');
        });
    }
};