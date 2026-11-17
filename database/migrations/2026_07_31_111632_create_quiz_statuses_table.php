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
        Schema::create('quiz_status', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('quiz_id');

            $table->unsignedInteger('total_enroll_user')->default(0);
            $table->unsignedInteger('total_played_user')->default(0);
            $table->unsignedInteger('total_complete_quiz_user')->default(0);
            $table->unsignedInteger('total_correct_ans')->default(0);

            $table->unsignedInteger('correct_answer_average_time')->default(0);

            $table->enum('status', ['pending', 'process', 'complete'])
                  ->default('pending');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('quiz_id');

            // Optional if these tables exist
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('quiz_id')->references('id')->on('quizzes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_status');
    }
};