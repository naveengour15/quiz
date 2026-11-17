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
          Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->string('page_type')
                  ->nullable()
                  ->after('banner_id');
        });

        Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->string('page_type')
                  ->nullable()
                  ->after('banner_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->dropColumn('page_type');
        });

        Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->dropColumn('page_type');
        });
    }
};
