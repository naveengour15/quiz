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
        Schema::table('quiz_rank_rewards', function (Blueprint $table) {
            $table->integer('expire_duration')->nullable()->comment('In days')->after('coupon_id');
        });

        
        Schema::table('reward_histories', function (Blueprint $table) {
             $table->integer('reward_point')->nullable()->after('reward_type');
        });
           
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_rank_rewards', function (Blueprint $table) {
             $table->dropColumn('expire_duration');
        });

         Schema::table('reward_histories', function (Blueprint $table) {
             $table->dropColumn('reward_point');
        });
    }
};
