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
             $table->text('question_text')->nullable()->after('order');
            $table->enum('type', ['mcq', 'true_false', 'image', 'audio', 'video', 'puzzle'])->default('mcq');
            $table->string('media_url')->nullable();
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
              $table->enum('bot_correct_answer', ['yes', 'no'])->default('no');
            $table->text('explanation')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('sub_category')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'adaptive'])->default('medium');
            $table->string('language', 30)->default('English');
            $table->unsignedInteger('time_limit_seconds')->default(30);
            $table->unsignedInteger('xp_value')->default(4);
            $table->decimal('negative_marking', 5, 2)->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->string('tags')->nullable();
            $table->enum('source', ['manual', 'ai_generated', 'imported'])->default('manual');
            $table->enum('moderation_status', ['pending', 'approved', 'rejected'])->default('approved');
             $table->timestamps();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_question', function (Blueprint $table) {
           
          
            $table->dropColumn([
                'question_text',
                'type',
                'media_url',
                'options',
                'correct_answer',
                'explanation',
                'category_id',
                'sub_category',
                'difficulty',
                'language',
                'time_limit_seconds',
                'xp_value',
                'negative_marking',
                'status',
                'tags',
                'source',
                'moderation_status',
                'bot_correct_answer',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
