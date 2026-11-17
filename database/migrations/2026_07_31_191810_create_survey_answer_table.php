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
        Schema::create('survey_answers', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('survey_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('question_id');

            // User's answer
            $table->enum('answer', ['yes', 'no'])->default('yes');

            $table->timestamps();
            $table->softDeletes();

            $table->index('tenant_id');
            $table->index('survey_id');
            $table->index('user_id');
            $table->index('question_id');

            // Prevent duplicate survey submission by same user
            $table->unique(['survey_id', 'user_id']);

            // Optional Foreign Keys
            // $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            // $table->foreign('survey_id')->references('id')->on('surveys')->cascadeOnDelete();
            // $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('survey_answers');
    }
};