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
        Schema::create('temp_quiz_status', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->enum('quiz_type', ['quiz', 'fff'])->default('fff');
            $table->unsignedBigInteger('quiz_id');

            $table->unsignedInteger('total_enroll_user')->default(0);
            $table->unsignedInteger('total_played_user')->default(0);
            $table->unsignedInteger('total_complete_quiz_user')->default(0);
            $table->unsignedInteger('total_intrested_user')->default(0);
            $table->unsignedInteger('total_correct_ans')->default(0);

            $table->unsignedInteger('correct_answer_average_time')->default(0);
            $table->unsignedBigInteger('current_question_id')->nullable();

            $table->enum('status', ['pending', 'process', 'complete'])
                  ->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('quiz_id');
            $table->unique(['tenant_id', 'quiz_id'], 'temp_quiz_status_tenant_quiz_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_quiz_status');
    }
};
