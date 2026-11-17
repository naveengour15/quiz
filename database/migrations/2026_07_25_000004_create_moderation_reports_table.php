<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->enum('item_type', ['question', 'quiz', 'comment'])->default('question');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->string('title');
            $table->string('reason')->nullable();
            $table->unsignedInteger('flagged_count')->default(1);
            $table->decimal('ai_score', 4, 2)->nullable();
            $table->text('reporter_comment')->nullable();
            $table->enum('status', ['pending', 'resolved'])->default('pending');
            $table->enum('decision', ['approve', 'edit_republish', 'remove', 'dismiss'])->nullable();
            $table->boolean('notify_creator')->default(false);
            $table->string('corrected_answer')->nullable();
            $table->text('internal_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_reports');
    }
};
