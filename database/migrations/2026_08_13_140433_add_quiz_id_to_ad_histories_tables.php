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
       
         Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->after('ad_id');
        });

        Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->after('ad_id');
        });
       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ad_click_histories', function (Blueprint $table) {
            $table->dropColumn('quiz_id');
        });

        Schema::table('ad_view_histories', function (Blueprint $table) {
            $table->dropColumn('quiz_id');
        });
    }
};
