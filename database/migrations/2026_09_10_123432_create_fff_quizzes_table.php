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
        Schema::create('fff_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();              
            $table->foreignId('quiz_id')->constrained('quizzes')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->enum('type', ['mcq', 'true_false', 'image', 'audio', 'video', 'puzzle'])->default('mcq');            
            $table->string('language', 30)->default('English'); // 'Hindi', 'English', 'Marathi', 'Telugu', 'Tamil', 'Kannada', 'Malayalam', 'Bengali', 'Gujarati', 'Odia', 'Punjabi', 'Urdu', 'Assamese', 'Kashmiri', 'Nepali', 'Sindhi'
            $table->text('question_text')->nullable();
            $table->text('question_audio')->nullable();
            $table->string('media_url')->nullable();
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->timestamps();
            $table->softDeletes();       
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fff_quizzes');
    }
};
