<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_status', function (Blueprint $table) {
            $table->unsignedBigInteger('current_question_id')
                ->nullable()
                ->after('correct_answer_average_time');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_status', function (Blueprint $table) {
            $table->dropColumn('current_question_id');
        });
    }
};