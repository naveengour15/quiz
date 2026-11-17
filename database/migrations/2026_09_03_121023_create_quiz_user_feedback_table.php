<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_user_feedback', function (Blueprint $table) {
            $table->id();

            // User / Tenant
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            // User Feedback
            $table->string('subject')->nullable();
            $table->text('message')->nullable();

            // Admin Review
            $table->text('review_note')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            // Admin Reply
            $table->text('reply_message')->nullable();
            $table->unsignedBigInteger('replied_by')->nullable();
            $table->timestamp('replied_at')->nullable();

            // Admin Close
            $table->text('close_note')->nullable();
            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();

            // Status
            $table->enum('status', [
                'pending',
                'reviewed',
                'replied',
                'closed',
            ])->default('pending');

            // Created / Updated
            $table->timestamps();

            // Soft Delete
            $table->softDeletes();

            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_user_feedback');
    }
};