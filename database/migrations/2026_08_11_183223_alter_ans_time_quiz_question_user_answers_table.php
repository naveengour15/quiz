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
        Schema::table('quiz_question_user_answers', function (Blueprint $table) {
              $table->time('ans_time', 3)->nullable()->change();
              $table->double('total_time', 3)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question_user_answers', function (Blueprint $table) {
            $table->time('ans_time')->nullable()->change();
             $table->integer('total_time', 3)->nullable()->change();
        });
    }
};
