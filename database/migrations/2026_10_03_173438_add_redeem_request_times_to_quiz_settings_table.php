<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->time('redeem_request_start_time')
                ->default('11:00:00')
                ->after('profile_update_point');

            $table->time('redeem_request_end_time')
                ->default('21:00:00')
                ->after('redeem_request_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn([
                'redeem_request_start_time',
                'redeem_request_end_time',
            ]);
        });
    }
};