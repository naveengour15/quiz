<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->text('question_text');
            $table->enum('type', ['mcq', 'true_false', 'image', 'audio', 'video', 'puzzle'])->default('mcq');
            $table->string('media_url')->nullable();
            $table->json('options')->nullable();
            $table->string('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
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

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
