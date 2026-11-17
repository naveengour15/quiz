<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallet_history', function (Blueprint $table) {
            $table->unsignedBigInteger('quiz_id')->nullable()->after('user_id');
            $table->unsignedBigInteger('referral_user_id')->nullable()->after('quiz_id');
            $table->unsignedBigInteger('survey_id')->nullable()->after('referral_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('wallet_history', function (Blueprint $table) {
            $table->dropColumn([
                'quiz_id',
                'referral_user_id',
                'survey_id',
            ]);
        });
    }
};