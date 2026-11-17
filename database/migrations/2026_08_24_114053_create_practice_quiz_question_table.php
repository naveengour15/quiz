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
        Schema::create('practice_quiz_question', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('tenant_id')->nullable();

           $table->unsignedBigInteger('practice_quiz_id');
           $table->unsignedBigInteger('question_id');

            $table->unsignedInteger('order')->default(1);
            $table->boolean('is_switch_question')->default(false);

            $table->text('question_text')->nullable();
            $table->string('question_audio')->nullable();

            $table->enum('type', [
                'mcq',
                'true_false',
                'image',
                'audio',
                'video',
                'puzzle'
            ])->default('mcq');

            $table->string('media_url')->nullable();

            $table->longText('options')->nullable();
            $table->string('correct_answer')->nullable();

            $table->text('hindi_question')->nullable();
            $table->json('hindi_options')->nullable();
            $table->string('hindi_correct_answer')->nullable();

            $table->string('audio_file')->nullable();

            $table->enum('bot_correct_answer', ['yes', 'no'])->default('no');

            $table->text('explanation')->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('sub_category')->nullable();

            $table->enum('difficulty', [
                'easy',
                'medium',
                'hard',
                'adaptive'
            ])->default('medium');

            $table->string('language', 30)->default('English');

            $table->unsignedInteger('time_limit_seconds')->default(30);

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->unsignedInteger('xp_value')->default(4);

            $table->decimal('negative_marking', 5, 2)->default(0.00);

            $table->enum('status', [
                'draft',
                'published',
                'archived'
            ])->default('draft');

            $table->string('tags')->nullable();

            $table->enum('source', [
                'manual',
                'ai_generated',
                'imported'
            ])->default('manual');

            $table->enum('moderation_status', [
                'pending',
                'approved',
                'rejected'
            ])->default('approved');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('practice_quiz_question');
    }
};
