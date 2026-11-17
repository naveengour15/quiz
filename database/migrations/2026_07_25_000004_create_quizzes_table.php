<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('title');
            $table->integer('category_id')->nullable();
            $table->integer('sub_category_id')->nullable();
            $table->integer('level_id')->nullable();
            $table->enum('type', ['standard', 'timed', 'rapid_fire', 'battle', 'live', 'adaptive'])->default('standard');
            $table->enum('visibility', ['public', 'private'])->default('public');
            $table->string('password')->nullable();
            $table->boolean('retake_allowed')->default(true);
            $table->unsignedInteger('max_attempts')->nullable();
            $table->unsignedTinyInteger('pass_percentage')->default(60);
            $table->boolean('certificate_on_pass')->default(false);
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->timestamp('availability_start')->nullable();
            $table->timestamp('availability_end')->nullable();
            
            $table->unsignedInteger('question_limit')->default(0);
            $table->unsignedInteger('no_of_players')->default(0);
            $table->decimal('no_of_points', 10, 2)->default(0);
            $table->decimal('total_reward_amount', 10, 2)->default(0);

            $table->boolean('negative_marking')->default(false);
            $table->enum('approve_status', ['pending', 'approved', 'rejected'])->default('pending');
            
            $table->enum('status', ['draft', 'scheduled', 'published', 'archived'])->default('draft');
            $table->enum('complete_status', ['pending', 'completed'])->default('pending');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_question');
        Schema::dropIfExists('quizzes');
    }
};
