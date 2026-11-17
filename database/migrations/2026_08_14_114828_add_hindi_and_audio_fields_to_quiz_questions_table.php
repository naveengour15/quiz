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
        Schema::table('quiz_question', function (Blueprint $table) {            
            $table->text('question_text')->nullable()->change();
            $table->text('hindi_question')->nullable()->after('correct_answer');
            $table->json('hindi_options')->nullable()->after('hindi_question');
            $table->string('hindi_correct_answer')->nullable()->after('hindi_options');
            $table->string('audio_file')->nullable()->after('hindi_correct_answer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question', function (Blueprint $table) {
                     
            $table->text('question_text')->change();
            $table->dropColumn([
                'hindi_question',
                'hindi_options',
                'hindi_correct_answer',
                'audio_file',
            ]);
        });
    }
};
