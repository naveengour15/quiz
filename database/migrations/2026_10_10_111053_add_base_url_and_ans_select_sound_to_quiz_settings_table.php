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
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->text('base_url')
                ->nullable()
                ->after('retailer_referral_limit');

            $table->text('ans_select_sound')
                ->nullable()
                ->after('base_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_settings', function (Blueprint $table) {
            $table->dropColumn([
                'base_url',
                'ans_select_sound',
            ]);
        });
    }
};
