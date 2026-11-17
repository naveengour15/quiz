<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * In-app notification inbox.
     *
     * audience = user  + user_id set  → sirf us user ko dikhe
     * audience = all   + user_id null → sabhi users ko dikhe
     *
     * Broadcast row update karne se sabko naya title/body dikhega.
     * Per-user read/hide `notification_reads` me store hota hai.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();

                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->enum('audience', ['user', 'all'])->default('user');

                $table->string('type', 64)->index();
                $table->string('title', 191);
                $table->text('body')->nullable();
                $table->string('image_url', 2000)->nullable();
                $table->string('deep_link', 2000)->nullable();
                $table->string('action', 64)->nullable();
                $table->json('data')->nullable();

                $table->string('related_type', 64)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();

                $table->string('topic', 191)->nullable();
                $table->enum('status', ['draft', 'published', 'cancelled'])->default('published');
                $table->timestamp('published_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();

                $table->timestamps();
                $table->softDeletes();

                $table->index(['audience', 'user_id', 'published_at'], 'notifications_inbox_idx');
                $table->index(['type', 'related_id'], 'notifications_related_idx');
                $table->index(['status', 'published_at'], 'notifications_status_idx');
            });
        }

        if (! Schema::hasTable('notification_reads')) {
            Schema::create('notification_reads', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('notification_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamp('read_at')->nullable();
                $table->timestamp('hidden_at')->nullable();
                $table->timestamps();

                $table->unique(['notification_id', 'user_id'], 'notification_reads_unique');
                $table->index(['user_id', 'read_at'], 'notification_reads_user_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_reads');
        Schema::dropIfExists('notifications');
    }
};
