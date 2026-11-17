<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quiz_settings', function (Blueprint $table) {
            $table->id();

            $table->integer('user_que_limit')->nullable()->default(null);
            $table->integer('user_quiz_limit')->nullable()->default(null);
            $table->integer('user_referral_limit')->nullable()->default(null);

            $table->integer('retailer_que_limit')->nullable()->default(null);
            $table->integer('retailer_quiz_limit')->nullable()->default(null);
            $table->integer('retailer_referral_limit')->nullable()->default(null);

            $table->timestamps();
        });

        DB::table('quiz_settings')->insert([
            'user_que_limit'           => 10,
            'user_quiz_limit'          => 4,
            'user_referral_limit'      => 5,
            'retailer_que_limit'       => 10,
            'retailer_quiz_limit'      => 10,
            'retailer_referral_limit'  => 10,
            'created_at'               => now(),
            'updated_at'               => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_settings');
    }
};