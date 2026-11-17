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
        // 1. leaderboards table rank & deleted_at composite index for fast pagination and sorting
        if (Schema::hasTable('leaderboards')) {
            Schema::table('leaderboards', function (Blueprint $table) {
                // Check if index does not exist before adding
                $table->index(['rank', 'deleted_at'], 'idx_leaderboards_rank_deleted');
            });
        }

        // 2. leader_point_history table user_id & created_at composite index for fast history fetching
        if (Schema::hasTable('leader_point_history')) {
            Schema::table('leader_point_history', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'idx_lph_user_created');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('leaderboards')) {
            Schema::table('leaderboards', function (Blueprint $table) {
                $table->dropIndex('idx_leaderboards_rank_deleted');
            });
        }

        if (Schema::hasTable('leader_point_history')) {
            Schema::table('leader_point_history', function (Blueprint $table) {
                $table->dropIndex('idx_lph_user_created');
            });
        }
    }
};
