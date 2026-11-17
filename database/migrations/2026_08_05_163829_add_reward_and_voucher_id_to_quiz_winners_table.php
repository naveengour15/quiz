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
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->string('reward')->nullable()->after('reward_id');
            $table->unsignedBigInteger('voucher_history_id')->nullable()->after('reward');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_winners', function (Blueprint $table) {
            $table->dropColumn(['reward', 'voucher_history_id']);
        });
    }
};