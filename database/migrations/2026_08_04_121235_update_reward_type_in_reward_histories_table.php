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
        Schema::table('reward_histories', function (Blueprint $table) {
            $table->enum('reward_type', ['point', 'coupon', 'gift', 'cashback', 'voucher'])
                ->default('point')
                ->change();

        });

        Schema::table('quiz_rank_rewards', function (Blueprint $table) {
            $table->enum('type', ['point', 'coupon', 'gift', 'cashback', 'voucher'])
                ->default('point')
                ->change();

        });


        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reward_histories', function (Blueprint $table) {
           
            $table->enum('reward_type', ['point', 'coupon', 'gift', 'cashback'])
                ->default('point')
                ->change();
        });

         Schema::table('quiz_rank_rewards', function (Blueprint $table) {
           
            $table->enum('type', ['point', 'coupon'])
                ->default('point')
                ->change();
        });


    }
};
