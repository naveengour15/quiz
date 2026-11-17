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

            $table->unsignedInteger('start_rank')
                ->nullable()
                ->after('quiz_id');

            $table->unsignedInteger('end_rank')
                ->nullable()
                ->after('start_rank');

            $table->unsignedBigInteger('voucher_id')
                ->nullable()
                ->after('reward');

            $table->json('voucher_histories_id')
                ->nullable()
                ->after('voucher_id');
            
            $table->softDeletes();

            $table->dropColumn(['rank_range', 'coupon_id']);
        });

       
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_rank_rewards', function (Blueprint $table) {

            $table->dropColumn([
                'start_rank',
                'end_rank',
                'voucher_id',
                'voucher_histories_id',
            ]);

            $table->unsignedInteger('rank_range')
                ->nullable()
                ->after('quiz_id');
            $table->unsignedInteger('coupon_id')
                ->nullable()
                ->after('reward');

            $table->dropSoftDeletes();
        });
    }
};