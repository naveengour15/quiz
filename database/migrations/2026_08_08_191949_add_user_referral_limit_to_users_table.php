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
        Schema::table('users', function (Blueprint $table) {
          $table->integer('user_referral_limit')->default(5)->after('referral_user_id');    
        });
        Schema::table('tenants', function (Blueprint $table) {
          $table->integer('referral_code')->nullable()->after('status');  
          $table->integer('user_referral_limit')->default(5)->after('referral_code');    
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('user_referral_limit');
        });
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('referral_code');
            $table->dropColumn('user_referral_limit');
        });
    }
};
