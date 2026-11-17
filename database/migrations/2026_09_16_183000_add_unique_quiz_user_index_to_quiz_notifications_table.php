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
        Schema::table('quiz_notifications', function (Blueprint $table) {
            $table->unique(
                ['quiz_id', 'user_id'],
                'idx_quiz_notif_quiz_user'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('quiz_notifications')) {
            Schema::table('quiz_notifications', function (Blueprint $table) {
                try {
                    $table->dropUnique('uniq_quiz_notif_quiz_user');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->index(['quiz_id', 'user_id'], 'idx_quiz_notif_quiz_user');
            });
        }
    }
};
