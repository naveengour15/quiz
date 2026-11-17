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
       // ad_view_histories
        Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->dropForeign(['ad_id']);
        });

        // ad_click_histories
        Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->dropForeign(['ad_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore FK for ad_view_histories
        Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->foreign('ad_id')
                ->references('id')
                ->on('ads')
                ->cascadeOnDelete();
        });

        // Restore FK for ad_click_histories
        Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->foreign('ad_id')
                ->references('id')
                ->on('ads')
                ->cascadeOnDelete();
        });
    }
};
