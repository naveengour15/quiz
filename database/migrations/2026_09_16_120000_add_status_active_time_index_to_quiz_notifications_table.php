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
        if (Schema::hasTable('quiz_notifications')) {
            Schema::table('quiz_notifications', function (Blueprint $table) {
                // Index for high performance pending notifications query
                $table->index(['status', 'notificationactiv_date_time'], 'idx_quiz_notif_status_active_time');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_notifications')) {
            Schema::table('quiz_notifications', function (Blueprint $table) {
                $table->dropIndex('idx_quiz_notif_status_active_time');
            });
        }
    }
};
